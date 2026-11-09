<?php $this->assign('title', 'Detalle de ' . $resource['singular']); ?>
<div class="breadcrumb"><?= $this->Html->link($resource['title'], ['action' => 'index']) ?><span>/</span>Detalle</div>
<div class="page-heading"><div><div class="eyebrow">FICHA DEL REGISTRO</div><h1><?= h($record->get('title') ?? $record->get('name') ?? $record->get('displayName') ?? ucfirst($resource['singular'])) ?></h1><p><?= h($resource['description']) ?></p></div><div class="heading-actions"><?= $this->Html->link('Editar ' . $resource['singular'], ['action' => 'edit', $record->id], ['class' => 'button button-primary']) ?><?= $this->Html->link('Volver al listado', ['action' => 'index'], ['class' => 'button button-secondary']) ?></div></div>
<section class="card detail-card"><h2>Información de <?= h($resource['singular']) ?></h2><dl class="detail-grid">
    <?php foreach ($resource['fields'] as $field => $meta): ?>
        <?php if ($field === 'password' || !empty($meta['transient'])) { continue; } ?>
        <div class="detail-item<?= ($meta['type'] ?? '') === 'textarea' ? ' full-width' : '' ?>"><dt><?= h($meta['label']) ?></dt><dd><?= $this->Admin->value($record, $field, $resource) ?></dd></div>
    <?php endforeach ?>
    <?php foreach (['createdAt', 'updatedAt', 'loggedAt'] as $field): ?>
        <?php if ($record->has($field)): ?><div class="detail-item"><dt><?= h($this->Admin->label($field, $resource)) ?></dt><dd><?= $this->Admin->value($record, $field, $resource) ?></dd></div><?php endif ?>
    <?php endforeach ?>
</dl></section>
<?php if ($resourceController === 'Routines'): ?><div class="related-shortcut card"><div><h2>Ejercicios y respuesta al entrenamiento</h2><p>Consulta las asignaciones de esta rutina y sus registros de esfuerzo.</p></div><?= $this->Html->link('Ver ejercicios de rutina', ['controller' => 'RoutineExercises', 'action' => 'index'], ['class' => 'button button-secondary']) ?></div><?php endif ?>
<section class="danger-zone"><div><h2>Eliminar <?= h($resource['singular']) ?></h2><p><?= h($resource['deleteWarning'] ?? 'La eliminación está sujeta a las relaciones existentes y no se puede deshacer.') ?></p></div>
    <?= $this->Form->create(null, ['url' => ['action' => 'delete', $record->id, '_method' => 'POST'], 'novalidate' => true, 'data-confirm' => '¿Eliminar este registro? ' . ($resource['deleteWarning'] ?? 'Esta acción no se puede deshacer.')]) ?><?= $this->Form->button('Eliminar registro', ['class' => 'button button-danger']) ?><?= $this->Form->end() ?>
</section>
