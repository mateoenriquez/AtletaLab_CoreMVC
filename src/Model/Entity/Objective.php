<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Objective extends Entity
{
    protected array $_accessible = ['code' => true, 'name' => true, 'targetRepMin' => true, 'targetRepMax' => true, 'minSuccessRate' => true];
}
