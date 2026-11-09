<?php $this->assign('title', $resource['title']); ?>
<div class="page-heading">
    <div><div class="eyebrow">ESPACIO DE ADMINISTRACIÓN</div><h1><?= h($resource['title']) ?></h1><p><?= h($resource['description']) ?></p></div>
    <?= $this->Html->link('+ Crear ' . $resource['singular'], ['action' => 'add'], ['class' => 'button button-primary']) ?>
</div>
<section class="card table-card">
    <div class="table-toolbar"><div><h2>Todos los registros</h2><span class="muted"><?= h($this->Paginator->counter('{{count}} registros en total')) ?></span></div>
        <?php if ($resource['search']): ?>
        <?= $this->Form->create(null, ['type' => 'get', 'novalidate' => true, 'class' => 'search-form', 'url' => ['action' => 'index']]) ?>
            <?= $this->Form->control('q', ['type' => 'search', 'label' => false, 'aria-label' => 'Buscar en ' . $resource['title'], 'placeholder' => 'Buscar…', 'value' => $search]) ?>
            <?= $this->Form->button('Buscar', ['class' => 'button button-secondary']) ?>
            <?php if ($search !== ''): ?><?= $this->Html->link('Limpiar', ['action' => 'index'], ['class' => 'subtle-link']) ?><?php endif ?>
        <?= $this->Form->end() ?>
        <?php endif ?>
    </div>
    <div class="table-scroll"><table>
        <thead><tr><?php foreach ($resource['index'] as $field): ?><th scope="col"><?php if (in_array($field, $sortable, true) && empty($resource['fields'][$field]['relatedController'])): ?><?= $this->Paginator->sort($field, $this->Admin->label($field, $resource)) ?><?php else: ?><?= h($this->Admin->label($field, $resource)) ?><?php endif ?></th><?php endforeach ?><th scope="col" class="actions-column">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($records as $record): ?>
            <tr><?php foreach ($resource['index'] as $field): ?><td><?= $this->Admin->value($record, $field, $resource) ?></td><?php endforeach ?>
                <td><div class="row-actions">
                    <?= $this->Html->link('Ver', ['action' => 'view', $record->id], ['class' => 'action-link']) ?>
                    <?= $this->Html->link('Editar', ['action' => 'edit', $record->id], ['class' => 'action-link']) ?>
                    <?= $this->Form->create(null, ['url' => ['action' => 'delete', $record->id, '_method' => 'POST'], 'novalidate' => true, 'class' => 'inline-form', 'data-confirm' => '¿Eliminar este registro? ' . ($resource['deleteWarning'] ?? 'Esta acción no se puede deshacer.')]) ?>
                        <?= $this->Form->button('Eliminar', ['class' => 'action-link danger']) ?>
                    <?= $this->Form->end() ?>
                </div></td>
            </tr>
        <?php endforeach ?>
        <?php if (count($records) === 0): ?><tr><td colspan="<?= count($resource['index']) + 1 ?>"><div class="empty-state"><strong>No hay registros para mostrar</strong><p><?= $search !== '' ? 'Prueba con otro término de búsqueda.' : 'Crea el primer registro para comenzar.' ?></p><?= $this->Html->link('Crear ' . $resource['singular'], ['action' => 'add'], ['class' => 'button button-secondary']) ?></div></td></tr><?php endif ?>
        </tbody>
    </table></div>
    <div class="table-footer"><span class="muted"><?= h($this->Paginator->counter('Página {{page}} de {{pages}}')) ?></span>
        <nav aria-label="Paginación"><ul class="pagination"><?= $this->Paginator->prev('Anterior') ?><?= $this->Paginator->numbers(['modulus' => 3]) ?><?= $this->Paginator->next('Siguiente') ?></ul></nav>
    </div>
</section>
