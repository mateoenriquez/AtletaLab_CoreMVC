<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class RoutineExercise extends Entity
{
    protected array $_accessible = ['targetSets' => true, 'targetReps' => true, 'currentWeight' => true, 'projectedWeight' => true, 'routineId' => true, 'exerciseId' => true];
    protected array $_virtual = ['displayName'];

    protected function _getDisplayName(): string
    {
        return ($this->routine?->title ?? 'Rutina') . ' · ' . ($this->exercise?->name ?? 'Ejercicio') . ' · ' . $this->targetSets . ' × ' . $this->targetReps;
    }
}
