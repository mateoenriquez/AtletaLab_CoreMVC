<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

class AppController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication', ['requireIdentity' => false]);
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        if ($this->getName() === 'Users' && $this->request->getParam('action') === 'login') {
            return;
        }
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            if ($this->request->accepts('application/json')) {
                $event->setResult($this->json(['error' => 'Tu sesión ha caducado. Inicia sesión de nuevo.'], 401));
                $event->stopPropagation();

                return;
            }

            $event->setResult($this->redirect(['controller' => 'Users', 'action' => 'login']));
            $event->stopPropagation();

            return;
        }
        // Releer el rol en cada petición revoca inmediatamente los permisos retirados.
        $currentUser = $this->fetchTable('Users')->find()->contain(['Roles'])->where(['Users.id' => $identity->getIdentifier()])->first();
        if (!$currentUser || $currentUser->role->code !== 'ADMIN') {
            $this->Authentication->logout();
            if ($this->request->accepts('application/json')) {
                $event->setResult($this->json(['error' => 'Este panel requiere el rol Administrador.'], 403));
                $event->stopPropagation();

                return;
            }
            throw new ForbiddenException('Este panel requiere el rol Administrador.');
        }
        $this->set('currentUser', $currentUser);

    }

    protected function json(array $data, int $status = 200): Response
    {
        return $this->response->withStatus($status)->withType('application/json')
            ->withStringBody(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    protected function positiveId(mixed $value): ?int
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^[1-9]\d{0,9}$/D', (string)$value) || (float)$value > 2147483647) {
            return null;
        }

        return (int)$value;
    }
}
