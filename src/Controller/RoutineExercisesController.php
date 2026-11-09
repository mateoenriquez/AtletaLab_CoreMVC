<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\ExercisesTable;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;

class RoutineExercisesController extends AdminController
{
    protected function formOptions(EntityInterface $record): array
    {
        $exercise = $record->get('exerciseId') ? $this->fetchTable('Exercises')->find()->where(['id' => $record->get('exerciseId')])->first() : null;
        $selected = $this->selectedFilter('muscleGroupFilter', $exercise?->muscleGroup ?? '');
        $this->set('filterValues', ['muscleGroupFilter' => $selected]);
        $this->set('cascade', ['parent' => 'muscleGroupFilter', 'child' => 'exerciseId', 'controller' => 'Exercises', 'action' => 'byMuscleGroup', 'mode' => 'query', 'query' => 'muscleGroup']);
        $exercises = [];
        if (in_array($selected, ExercisesTable::MUSCLE_GROUPS, true)) {
            foreach ($this->fetchTable('Exercises')->find()->where(['muscleGroup' => $selected])->orderBy(['name' => 'ASC']) as $item) {
                $exercises[$item->id] = $item->name . ' · coef. ' . number_format((float)$item->fatigueCoefficient, 2, '.', '');
            }
        }

        return ['routineId' => $this->fetchTable('Routines')->find('list')->orderBy(['title' => 'ASC'])->toArray(), 'exerciseId' => $exercises];
    }

    protected function validateForm(EntityInterface $record): void
    {
        $group = $this->request->getData('muscleGroupFilter');
        if (!$this->positiveId($record->get('exerciseId')) || !is_string($group) || !in_array($group, ExercisesTable::MUSCLE_GROUPS, true)
            || !$this->fetchTable('Exercises')->exists(['id' => $record->get('exerciseId'), 'muscleGroup' => $group])) {
            $record->setError('exerciseId', ['cascade' => 'Selecciona un ejercicio del grupo muscular elegido.']);
        }
    }

    public function byRoutine(string $routineId): Response
    {
        $this->request->allowMethod(['get']);
        $id = $this->positiveId($routineId);
        if (!$id || !$this->fetchTable('Routines')->exists(['id' => $id])) {
            return $this->json(['error' => 'La rutina seleccionada no existe.'], 404);
        }
        $assignments = $this->fetchTable()->find()->contain(['Routines', 'Exercises'])->where(['RoutineExercises.routineId' => $id])->orderBy(['Exercises.name' => 'ASC', 'RoutineExercises.id' => 'ASC']);
        $data = [];
        foreach ($assignments as $assignment) {
            $data[] = ['id' => $assignment->id, 'label' => $assignment->displayName, 'exerciseName' => $assignment->exercise->name, 'targetSets' => $assignment->targetSets, 'targetReps' => $assignment->targetReps];
        }

        return $this->json(['data' => $data]);
    }
}
