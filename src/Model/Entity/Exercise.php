<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Exercise extends Entity
{
    protected array $_accessible = ['code' => true, 'name' => true, 'muscleGroup' => true, 'fatigueCoefficient' => true, 'description' => true];
}
