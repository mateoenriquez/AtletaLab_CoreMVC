<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class ArchetypesTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('archetypes');
        $this->setDisplayField('name');
        $this->timestamps();
        $this->hasMany('Users', ['foreignKey' => 'archetypeId']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $this->code($validator);
        $this->text($validator, 'name', 50);
        foreach (['incPercentage', 'stagnationPenalty', 'deloadPercentage'] as $field) {
            $this->decimalRange($validator, $field, 0.5, 50.0);
        }
        $this->decimalRange($validator, 'maxFatigueThreshold', 0.0, 10000.0);
        $validator->greaterThan('maxFatigueThreshold', 0, 'El umbral de fatiga debe ser mayor que cero para evitar divisiones por cero.');
        $this->decimalRange($validator, 'rpeLimit', 5.0, 9.5, 1);

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $this->unique($rules, 'code');
        $this->unique($rules, 'name');
        $this->restrictDelete($rules, 'Users', 'archetypeId');

        return $rules;
    }
}
