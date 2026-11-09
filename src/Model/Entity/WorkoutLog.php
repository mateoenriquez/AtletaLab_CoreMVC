<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class WorkoutLog extends Entity
{
    protected array $_accessible = ['setsCompleted' => true, 'repsAchieved' => true, 'weightUsed' => true, 'rpe' => true, 'routineExerciseId' => true];
    protected array $_virtual = ['displayName'];

    protected function _getDisplayName(): string
    {
        return ($this->routine_exercise?->displayName ?? 'Entrenamiento') . ' · ' . ($this->loggedAt?->format('Y-m-d H:i') ?? 'Nuevo registro');
    }
}
