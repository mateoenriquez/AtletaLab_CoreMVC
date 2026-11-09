<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class ObjectivesTable extends AppTable
{
    public const REP_ERROR = 'Error de coherencia: El número máximo de repeticiones no puede ser menor al mínimo.';

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('objectives');
        $this->setDisplayField('name');
        $this->timestamps();
        $this->hasMany('Routines', ['foreignKey' => 'objectiveId']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $this->code($validator);
        $this->text($validator, 'name', 50);
        $this->integerRange($validator, 'targetRepMin', 1, 50);
        $this->integerRange($validator, 'targetRepMax', 1, 50);
        $this->decimalRange($validator, 'minSuccessRate', 50.0, 100.0);
        $validator->add('targetRepMax', 'coherentRange', [
            'rule' => static fn(mixed $value, array $context): bool => !isset($context['data']['targetRepMin'])
                || !is_scalar($context['data']['targetRepMin'])
                || (is_scalar($value) && (float)$value >= (float)$context['data']['targetRepMin']),
            'message' => self::REP_ERROR,
        ]);

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $this->unique($rules, 'code');
        $this->restrictDelete($rules, 'Routines', 'objectiveId');
        $rules->add(static fn(EntityInterface $entity): bool => $entity->get('targetRepMax') >= $entity->get('targetRepMin'), 'coherentRange', ['errorField' => 'targetRepMax', 'message' => self::REP_ERROR]);

        return $rules;
    }
}
