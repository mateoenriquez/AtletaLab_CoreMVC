<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Database\Exception\QueryException;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\ORM\Query\SelectQuery;

abstract class AdminController extends AppController
{
    protected array $resource;

    public function initialize(): void
    {
        parent::initialize();
        $resources = require CONFIG . 'admin.php';
        $this->resource = $resources[$this->getName()];
        $this->set('resource', $this->resource);
        $this->set('resourceController', $this->getName());
    }

    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $table = $this->fetchTable();
        $query = $table->find()->contain($this->resource['contain']);
        $rawSearch = $this->request->getQuery('q', '');
        $search = is_string($rawSearch) ? mb_substr(trim($rawSearch), 0, 100) : '';
        if ($search !== '' && $this->resource['search']) {
            $conditions = [];
            foreach ($this->resource['search'] as $field) {
                $conditions[$table->aliasField($field) . ' LIKE'] = '%' . $search . '%';
            }
            $query->where(['OR' => $conditions]);
        }
        $sortable = array_values(array_filter($table->getSchema()->columns(), static fn(string $field): bool => $field !== 'password'));
        $records = $this->paginate($query, ['limit' => 15, 'maxLimit' => 100, 'order' => ['id' => 'DESC'], 'sortableFields' => $sortable]);
        $this->set(compact('records', 'search', 'sortable'));
    }

    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);
        $record = $this->fetchTable()->get($id, contain: $this->resource['contain']);
        $this->set(compact('record'));
    }

    public function add(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);

        return $this->form($this->fetchTable()->newEmptyEntity(), false);
    }

    public function edit(string $id): ?Response
    {
        $this->request->allowMethod(['get', 'post', 'put', 'patch']);

        return $this->form($this->fetchTable()->get($id, contain: $this->resource['contain']), true);
    }

    protected function form(EntityInterface $record, bool $editing): ?Response
    {
        $table = $this->fetchTable();
        if ($this->request->is(['post', 'put', 'patch'])) {
            $allowed = array_keys(array_filter($this->resource['fields'], static fn(array $field): bool => empty($field['transient'])));
            $data = array_intersect_key((array)$this->request->getData(), array_flip($allowed));
            if ($editing && ($data['password'] ?? null) === '') {
                unset($data['password']);
            }
            $record = $table->patchEntity($record, $data, ['associated' => []]);
            $this->validateForm($record);
            foreach ($this->resource['fields'] as $field => $meta) {
                if (isset($meta['relatedController']) && empty($meta['optional']) && !array_key_exists($field, $data)) {
                    $record->setError($field, ['required' => 'Selecciona una opción de la lista.']);
                }
            }
            try {
                if (!$record->hasErrors() && $table->save($record, ['associated' => []])) {
                    $this->Flash->success($editing ? 'Los cambios se guardaron correctamente.' : 'El registro se creó correctamente.');

                    return $this->redirect(['action' => 'view', $record->get('id')]);
                }
            } catch (QueryException $exception) {
                Log::error('Conflicto al guardar ' . $this->getName() . ': ' . $exception->getMessage());
                $record->setError('_form', ['database' => 'No se pudo guardar. Comprueba valores únicos y registros relacionados; otro administrador pudo modificarlos.']);
            }
            $this->Flash->error('No se pudo guardar. Revisa los errores de validación.');
            $this->response = $this->response->withStatus(422);
        }
        $this->set(compact('record', 'editing'));
        $this->set('formOptions', $this->formOptions($record));

        return null;
    }

    protected function formOptions(EntityInterface $record): array
    {
        return [];
    }

    protected function validateForm(EntityInterface $record): void
    {
    }

    protected function selectedFilter(string $field, mixed $default = ''): string
    {
        $value = $this->request->is(['post', 'put', 'patch']) ? $this->request->getData($field, '') : $default;

        return is_scalar($value) ? (string)$value : '';
    }

    protected function userOptions(SelectQuery $query): array
    {
        return $query->find('list', keyField: 'id', valueField: 'displayName')->orderBy(['firstName' => 'ASC', 'lastName' => 'ASC'])->toArray();
    }

    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $table = $this->fetchTable();
        $record = $table->get($id);
        if ($this->getName() === 'Users' && (string)$id === (string)$this->Authentication->getIdentity()->getIdentifier()) {
            $this->Flash->error('No puedes eliminar tu propia cuenta mientras usas el panel.');

            return $this->redirect(['action' => 'view', $id]);
        }
        try {
            if ($table->delete($record)) {
                $this->Flash->success('El registro se eliminó correctamente.');
            } else {
                $this->Flash->error('No se puede eliminar: existen registros relacionados.');
            }
        } catch (QueryException $exception) {
            Log::error('Conflicto al eliminar ' . $this->getName() . ': ' . $exception->getMessage());
            $this->Flash->error('No se puede eliminar: existen registros relacionados o una modificación simultánea.');
        }

        return $this->redirect(['action' => 'index']);
    }
}
