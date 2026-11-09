<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class RoutineExercisesTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('routine_exercises');
        $this->setDisplayField('displayName');
        $this->belongsTo('Routines', ['foreignKey' => 'routineId', 'joinType' => 'INNER']);
        $this->belongsTo('Exercises', ['foreignKey' => 'exerciseId', 'joinType' => 'INNER']);
        $this->hasMany('WorkoutLogs', ['foreignKey' => 'routineExerciseId', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $this->integerRange($validator, 'targetSets', 1);
        $this->integerRange($validator, 'targetReps', 1);
        $this->decimalRange($validator, 'currentWeight', 0.0, 9999.99);
        $this->decimalRange($validator, 'projectedWeight', 0.0, 9999.99);
        $this->foreignKey($validator, 'routineId');
        $this->foreignKey($validator, 'exerciseId');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $this->reference($rules, 'routineId', 'Routines');
        $this->reference($rules, 'exerciseId', 'Exercises');

        return $rules;
    }
}
