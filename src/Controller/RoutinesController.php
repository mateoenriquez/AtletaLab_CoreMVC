<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\EntityInterface;

class RoutinesController extends AdminController
{
    protected function formOptions(EntityInterface $record): array
    {
        $athlete = $record->get('athleteId') ? $this->fetchTable('Users')->find()->where(['id' => $record->get('athleteId')])->first() : null;
        $selected = $this->selectedFilter('archetypeFilter', $athlete?->archetypeId ?? '');
        $this->set('filterValues', ['archetypeFilter' => $selected]);
        $this->set('cascade', ['parent' => 'archetypeFilter', 'child' => 'athleteId', 'controller' => 'Users', 'action' => 'athletesByArchetype', 'mode' => 'path']);
        $id = $this->positiveId($selected);
        $athletes = $id ? $this->userOptions($this->fetchTable('Users')->find()->innerJoinWith('Roles', static fn($query) => $query->where(['Roles.code' => 'ATHLETE']))->where(['Users.archetypeId' => $id])) : [];

        return [
            'archetypeFilter' => $this->fetchTable('Archetypes')->find('list')->orderBy(['name' => 'ASC'])->toArray(),
            'athleteId' => $athletes,
            'trainerId' => $this->userOptions($this->fetchTable('Users')->find()->innerJoinWith('Roles', static fn($query) => $query->where(['Roles.code' => 'TRAINER']))),
            'objectiveId' => $this->fetchTable('Objectives')->find('list')->orderBy(['name' => 'ASC'])->toArray(),
        ];
    }

    protected function validateForm(EntityInterface $record): void
    {
        $id = $this->positiveId($this->request->getData('archetypeFilter'));
        if (!$id || !$this->positiveId($record->get('athleteId')) || !$this->fetchTable('Users')->exists(['id' => $record->get('athleteId'), 'archetypeId' => $id])) {
            $record->setError('athleteId', ['cascade' => 'Selecciona un atleta que pertenezca al arquetipo elegido.']);
        }
    }
}
