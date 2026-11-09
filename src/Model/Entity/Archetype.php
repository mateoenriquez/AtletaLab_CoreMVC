<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Archetype extends Entity
{
    protected array $_accessible = ['code' => true, 'name' => true, 'incPercentage' => true, 'stagnationPenalty' => true, 'deloadPercentage' => true, 'maxFatigueThreshold' => true, 'rpeLimit' => true];
}
