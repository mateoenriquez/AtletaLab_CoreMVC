<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class UsersTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('users');
        $this->setDisplayField('displayName');
        $this->timestamps();
        $this->belongsTo('Roles', ['foreignKey' => 'roleId', 'joinType' => 'INNER']);
        $this->belongsTo('Archetypes', ['foreignKey' => 'archetypeId']);
        $this->hasMany('AthleteRoutines', ['className' => 'Routines', 'foreignKey' => 'athleteId']);
        $this->hasMany('TrainerRoutines', ['className' => 'Routines', 'foreignKey' => 'trainerId']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $this->text($validator, 'email', 200);
        $validator->email('email', false, 'Introduce un correo electrónico válido.');
        $this->text($validator, 'firstName', 50);
        $this->text($validator, 'lastName', 50);
        $validator->scalar('password', 'Introduce una contraseña válida.')
            ->requirePresence('password', 'create', 'La contraseña es obligatoria.')
            ->notEmptyString('password', 'La contraseña es obligatoria.', 'create')
            ->allowEmptyString('password', null, 'update')
            ->minLength('password', 12, 'La contraseña debe tener al menos 12 caracteres.')
            ->add('password', 'bcryptLength', ['rule' => static fn(mixed $value): bool => is_string($value) && strlen($value) <= 72 && !str_contains($value, "\0"), 'message' => 'La contraseña admite como máximo 72 bytes y no puede contener bytes nulos.']);
        $validator->requirePresence('birthDate', 'create', 'La fecha de nacimiento es obligatoria.')
            ->notEmptyDate('birthDate', 'La fecha de nacimiento es obligatoria.')
            ->date('birthDate', ['ymd'], 'Introduce una fecha real con formato AAAA-MM-DD.')
            ->add('birthDate', 'minimumAge', [
                'rule' => static function (mixed $value): bool {
                    if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) !== 1) {
                        return false;
                    }
                    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

                    return $date !== false && $date->format('Y-m-d') === $value && $date <= new \DateTimeImmutable((string)Date::today()->subYears(12)->format('Y-m-d'));
                },
                'message' => 'El usuario debe tener al menos 12 años de edad.',
            ]);
        $this->foreignKey($validator, 'roleId');
        $this->foreignKey($validator, 'archetypeId', true);

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $this->unique($rules, 'email');
        $this->reference($rules, 'roleId', 'Roles');
        $this->reference($rules, 'archetypeId', 'Archetypes');
        $this->restrictDelete($rules, 'AthleteRoutines', 'athleteId');
        $this->restrictDelete($rules, 'TrainerRoutines', 'trainerId');
        $rules->addUpdate(function (EntityInterface $entity): bool {
            $role = $this->Roles->find()->select(['code'])->where(['id' => $entity->get('roleId')])->first();
            if (!$role) {
                return false;
            }
            if ($role->code !== 'ATHLETE' && $this->AthleteRoutines->exists(['athleteId' => $entity->get('id')])) {
                return false;
            }

            return $role->code === 'TRAINER' || !$this->TrainerRoutines->exists(['trainerId' => $entity->get('id')]);
        }, 'assignedRole', ['errorField' => 'roleId', 'message' => 'No se puede cambiar el rol: el usuario está asignado a rutinas con su rol actual.']);

        return $rules;
    }
}
