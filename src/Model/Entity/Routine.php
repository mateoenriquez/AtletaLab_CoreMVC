<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Routine extends Entity
{
    protected array $_accessible = ['code' => true, 'title' => true, 'description' => true, 'status' => true, 'athleteId' => true, 'trainerId' => true, 'objectiveId' => true, 'effectivenessScore' => true, 'isBestPractice' => true];
}
