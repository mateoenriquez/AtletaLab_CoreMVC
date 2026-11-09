<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class RolesTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('roles');
        $this->setDisplayField('name');
        $this->timestamps();
        $this->hasMany('Users', ['foreignKey' => 'roleId']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $this->code($validator);
        $this->text($validator, 'name', 50);
        $this->text($validator, 'description', 200, false);

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $this->unique($rules, 'code');
        $this->unique($rules, 'name');
        $this->restrictDelete($rules, 'Users', 'roleId');
        $rules->addUpdate(static function (EntityInterface $entity): bool {
            return !in_array($entity->getOriginal('code'), ['ADMIN', 'TRAINER', 'ATHLETE'], true) || !$entity->isDirty('code');
        }, 'reservedCode', ['errorField' => 'code', 'message' => 'Los códigos ADMIN, TRAINER y ATHLETE son estables porque controlan los permisos del sistema.']);

        return $rules;
    }
}
