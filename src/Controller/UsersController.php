<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\EntityInterface;
use Cake\Http\Response;

class UsersController extends AdminController
{
    protected function formOptions(EntityInterface $record): array
    {
        return [
            'roleId' => $this->fetchTable('Roles')->find('list')->orderBy(['name' => 'ASC'])->toArray(),
            'archetypeId' => $this->fetchTable('Archetypes')->find('list')->orderBy(['name' => 'ASC'])->toArray(),
        ];
    }

    protected function validateForm(EntityInterface $record): void
    {
        if (!$record->isNew() && (string)$record->get('id') === (string)$this->Authentication->getIdentity()->getIdentifier()) {
            $role = $this->positiveId($record->get('roleId')) ? $this->fetchTable('Roles')->find()->where(['id' => $record->get('roleId')])->first() : null;
            if (!$role || $role->code !== 'ADMIN') {
                $record->setError('roleId', ['selfRole' => 'No puedes retirar tu propio rol Administrador mientras usas el panel.']);
            }
        }
    }

    public function login(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $this->viewBuilder()->setLayout('login');
        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            $user = $this->fetchTable()->find()->contain(['Roles'])->where(['Users.id' => $this->Authentication->getIdentity()->getIdentifier()])->first();
            if ($user && $user->role->code === 'ADMIN') {
                return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
            }
            $this->Authentication->logout();
            $this->Flash->error('Este panel requiere una cuenta con rol Administrador.');
            $this->response = $this->response->withStatus(403);
        } elseif ($this->request->is('post')) {
            $this->Flash->error('Correo o contraseña incorrectos.');
            $this->response = $this->response->withStatus(401);
        }

        return null;
    }

    public function logout(): Response
    {
        $this->request->allowMethod(['post']);
        $this->Authentication->logout();
        $this->request->getSession()->renew();

        return $this->redirect(['action' => 'login']);
    }

    public function athletesByArchetype(string $archetypeId): Response
    {
        $this->request->allowMethod(['get']);
        $id = $this->positiveId($archetypeId);
        if (!$id || !$this->fetchTable('Archetypes')->exists(['id' => $id])) {
            return $this->json(['error' => 'El arquetipo seleccionado no existe.'], 404);
        }
        $users = $this->fetchTable()->find()->innerJoinWith('Roles', static fn($query) => $query->where(['Roles.code' => 'ATHLETE']))
            ->where(['Users.archetypeId' => $id])->orderBy(['Users.firstName' => 'ASC', 'Users.lastName' => 'ASC']);
        $data = [];
        foreach ($users as $user) {
            $data[] = ['id' => $user->id, 'label' => $user->displayName];
        }

        return $this->json(['data' => $data]);
    }
}
