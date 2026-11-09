<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class WorkoutLogsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('workout_logs');
        $this->setDisplayField('displayName');
        $this->addBehavior('Timestamp', ['events' => ['Model.beforeSave' => ['loggedAt' => 'new']]]);
        $this->belongsTo('RoutineExercises', ['foreignKey' => 'routineExerciseId', 'joinType' => 'INNER']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $this->integerRange($validator, 'setsCompleted', 1);
        $this->integerRange($validator, 'repsAchieved', 0);
        $this->decimalRange($validator, 'weightUsed', 0.0, 9999.99);
        $this->decimalRange($validator, 'rpe', 1.0, 10.0, 1, true);
        $this->foreignKey($validator, 'routineExerciseId');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $this->reference($rules, 'routineExerciseId', 'RoutineExercises');

        return $rules;
    }
}
