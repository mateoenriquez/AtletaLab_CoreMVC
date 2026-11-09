<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class ExercisesTable extends AppTable
{
    public const MUSCLE_GROUPS = ['Pecho', 'Espalda', 'Pierna', 'Hombro', 'Brazo', 'Core'];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('exercises');
        $this->setDisplayField('name');
        $this->timestamps();
        $this->hasMany('RoutineExercises', ['foreignKey' => 'exerciseId']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $this->code($validator);
        $this->text($validator, 'name', 100);
        $this->text($validator, 'description', 200, false);
        $validator->requirePresence('muscleGroup', 'create', 'Selecciona un grupo muscular.')
            ->notEmptyString('muscleGroup', 'Selecciona un grupo muscular.')
            ->inList('muscleGroup', self::MUSCLE_GROUPS, 'El grupo muscular no pertenece a la lista permitida.');
        $this->decimalRange($validator, 'fatigueCoefficient', 0.10, 5.00, 2, true);

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $this->unique($rules, 'code');
        $this->restrictDelete($rules, 'RoutineExercises', 'exerciseId');

        return $rules;
    }
}
