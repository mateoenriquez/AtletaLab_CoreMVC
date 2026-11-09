<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\EntityInterface;

class WorkoutLogsController extends AdminController
{
    protected function formOptions(EntityInterface $record): array
    {
        $assignment = $record->get('routineExerciseId') ? $this->fetchTable('RoutineExercises')->find()->where(['id' => $record->get('routineExerciseId')])->first() : null;
        $selected = $this->selectedFilter('routineFilter', $assignment?->routineId ?? '');
        $this->set('filterValues', ['routineFilter' => $selected]);
        $this->set('cascade', ['parent' => 'routineFilter', 'child' => 'routineExerciseId', 'controller' => 'RoutineExercises', 'action' => 'byRoutine', 'mode' => 'path']);
        $id = $this->positiveId($selected);
        $assignments = $id ? $this->fetchTable('RoutineExercises')->find('list', keyField: 'id', valueField: 'displayName')->contain(['Routines', 'Exercises'])->where(['RoutineExercises.routineId' => $id])->orderBy(['Exercises.name' => 'ASC'])->toArray() : [];

        return ['routineFilter' => $this->fetchTable('Routines')->find('list')->orderBy(['title' => 'ASC'])->toArray(), 'routineExerciseId' => $assignments];
    }

    protected function validateForm(EntityInterface $record): void
    {
        $id = $this->positiveId($this->request->getData('routineFilter'));
        if (!$id || !$this->positiveId($record->get('routineExerciseId')) || !$this->fetchTable('RoutineExercises')->exists(['id' => $record->get('routineExerciseId'), 'routineId' => $id])) {
            $record->setError('routineExerciseId', ['cascade' => 'Selecciona un ejercicio asignado a la rutina elegida.']);
        }
    }
}
