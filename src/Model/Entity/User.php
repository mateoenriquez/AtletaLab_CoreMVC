<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = ['email' => true, 'firstName' => true, 'lastName' => true, 'password' => true, 'birthDate' => true, 'roleId' => true, 'archetypeId' => true];
    protected array $_hidden = ['password'];
    protected array $_virtual = ['displayName'];

    protected function _setPassword(string $password): string
    {
        if ($password === '') {
            return (string)($this->_fields['password'] ?? '');
        }

        return (new DefaultPasswordHasher(['hashType' => PASSWORD_BCRYPT, 'hashOptions' => ['cost' => 12]]))->hash($password);
    }

    protected function _getDisplayName(): string
    {
        return trim((string)$this->firstName . ' ' . (string)$this->lastName) . ' · ' . (string)$this->email;
    }
}
