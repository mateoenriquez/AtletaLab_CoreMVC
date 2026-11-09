<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\ExercisesTable;
use Cake\Http\Response;

class ExercisesController extends AdminController
{
    public function byMuscleGroup(): Response
    {
        $this->request->allowMethod(['get']);
        $muscleGroup = $this->request->getQuery('muscleGroup');
        if (!is_string($muscleGroup) || !in_array($muscleGroup, ExercisesTable::MUSCLE_GROUPS, true)) {
            return $this->json(['error' => 'Selecciona un grupo muscular válido.'], 400);
        }
        $rows = $this->fetchTable()->find()->select(['id', 'name', 'fatigueCoefficient'])->where(['muscleGroup' => $muscleGroup])->orderBy(['name' => 'ASC']);
        $data = [];
        foreach ($rows as $row) {
            $data[] = ['id' => $row->id, 'label' => $row->name . ' · coef. ' . number_format((float)$row->fatigueCoefficient, 2, '.', ''), 'name' => $row->name, 'fatigueCoefficient' => $row->fatigueCoefficient];
        }

        return $this->json(['data' => $data]);
    }
}
