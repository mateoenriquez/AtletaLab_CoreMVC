<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class RoutinesTable extends AppTable
{
    public const STATUSES = ['Activa', 'En Descarga', 'Estancada', 'Completada'];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('routines');
        $this->setDisplayField('title');
        $this->timestamps();
        $this->belongsTo('Athletes', ['className' => 'Users', 'foreignKey' => 'athleteId', 'propertyName' => 'athlete', 'joinType' => 'INNER']);
        $this->belongsTo('Trainers', ['className' => 'Users', 'foreignKey' => 'trainerId', 'propertyName' => 'trainer', 'joinType' => 'INNER']);
        $this->belongsTo('Objectives', ['foreignKey' => 'objectiveId', 'joinType' => 'INNER']);
        $this->hasMany('RoutineExercises', ['foreignKey' => 'routineId', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $this->code($validator);
        $this->text($validator, 'title', 100);
        $this->text($validator, 'description', 10000, false);
        $validator->requirePresence('status', 'create', 'Selecciona el estado de la rutina.')
            ->notEmptyString('status', 'Selecciona el estado de la rutina.')
            ->inList('status', self::STATUSES, 'El estado de la rutina no es válido.');
        foreach (['athleteId', 'trainerId', 'objectiveId'] as $field) {
            $this->foreignKey($validator, $field);
        }
        $this->decimalRange($validator, 'effectivenessScore', 0.0, 100.0);
        $validator->boolean('isBestPractice', 'Selecciona un valor booleano válido.')->notEmptyString('isBestPractice', 'Indica si es una mejor práctica.');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $this->unique($rules, 'code');
        $this->reference($rules, 'athleteId', 'Athletes');
        $this->reference($rules, 'trainerId', 'Trainers');
        $this->reference($rules, 'objectiveId', 'Objectives');
        $rules->add(static fn(EntityInterface $entity): bool => (int)$entity->get('athleteId') !== (int)$entity->get('trainerId'), 'distinctUsers', ['errorField' => 'trainerId', 'message' => 'El atleta y el entrenador deben ser usuarios distintos.']);
        foreach (['athleteId' => 'ATHLETE', 'trainerId' => 'TRAINER'] as $field => $role) {
            $rules->add(function (EntityInterface $entity) use ($field, $role): bool {
                return $this->Athletes->find()->innerJoinWith('Roles', static fn($query) => $query->where(['Roles.code' => $role]))
                    ->where([$this->Athletes->aliasField('id') => $entity->get($field)])->count() === 1;
            }, 'role_' . $field, ['errorField' => $field, 'message' => $role === 'ATHLETE' ? 'Selecciona un usuario con rol Atleta.' : 'Selecciona un usuario con rol Entrenador.']);
        }

        return $rules;
    }
}
