<?php
$this->assign('title', ($editing ? 'Editar ' : 'Crear ') . $resource['singular']);
$filterValues = $filterValues ?? [];
$cascade = $cascade ?? null;
$endpoint = $cascade ? $this->Url->build(array_merge(['controller' => $cascade['controller'], 'action' => $cascade['action']], $cascade['mode'] === 'path' ? [0] : [])) : '';
?>
<div class="breadcrumb"><?= $this->Html->link($resource['title'], ['action' => 'index']) ?><span>/</span><?= $editing ? 'Editar' : 'Crear' ?></div>
<div class="page-heading"><div><div class="eyebrow"><?= $editing ? 'ACTUALIZAR INFORMACIÓN' : 'NUEVO REGISTRO' ?></div><h1><?= $editing ? 'Editar' : 'Crear' ?> <?= h($resource['singular']) ?></h1><p><?= h($resource['description']) ?></p></div></div>
<div class="form-layout">
<section class="card form-card">
    <?php if ($record->hasErrors()): ?>
    <div class="validation-summary" role="alert" tabindex="-1" data-validation-summary><strong>No se guardaron los datos. Revisa lo siguiente:</strong><ul><?php foreach ($this->Admin->errors($record->getErrors(), $resource) as $message): ?><li><?= h($message) ?></li><?php endforeach ?></ul></div>
    <?php endif ?>
    <?= $this->Form->create($record, ['novalidate' => true, 'class' => 'admin-form', 'valueSources' => ['data', 'context'], 'data-cascade-parent' => $cascade['parent'] ?? '', 'data-cascade-child' => $cascade['child'] ?? '', 'data-cascade-endpoint' => $endpoint, 'data-cascade-mode' => $cascade['mode'] ?? '', 'data-cascade-query' => $cascade['query'] ?? '']) ?>
        <div class="section-heading"><h2>Información de <?= h($resource['singular']) ?></h2><p>Los campos con * son obligatorios.</p></div>
        <div class="form-grid">
        <?php foreach ($resource['fields'] as $field => $meta): ?>
            <?php
            $options = ['label' => $meta['label'], 'type' => $meta['type'], 'id' => $field, 'required' => empty($meta['optional']) && !($editing && $field === 'password')];
            if (isset($meta['step'])) { $options['step'] = $meta['step']; }
            if (isset($meta['default'])) { $options['default'] = $meta['default']; }
            if ($meta['type'] === 'select') {
                $options['options'] = $formOptions[$field] ?? $meta['options'] ?? [];
                $options['empty'] = !empty($meta['optional']) ? 'Sin asignar' : 'Selecciona una opción';
                if (isset($filterValues[$field])) { $options['value'] = $filterValues[$field]; }
            }
            if ($field === 'password') { $options['value'] = ''; $options['autocomplete'] = 'new-password'; }
            $submittedValue = $this->getRequest()->getData($field);
            if (is_array($submittedValue) || is_object($submittedValue)) { $options['value'] = ''; }
            if ($meta['type'] === 'date' && $record->getError($field)) {
                $options['type'] = 'text';
                $options['value'] = is_scalar($submittedValue) ? (string)$submittedValue : '';
                $options['placeholder'] = 'AAAA-MM-DD';
            }
            if ($field === 'email') { $options['autocomplete'] = 'email'; }
            if ($meta['type'] === 'checkbox') { $options['required'] = false; }
            if (!empty($meta['hint'])) { $options['aria-describedby'] = $field . '-hint'; }
            if ($record->getError($field)) { $options['aria-invalid'] = 'true'; }
            ?>
            <div class="form-field<?= in_array($meta['type'], ['textarea', 'checkbox'], true) ? ' full-width' : '' ?>">
                <?= $this->Form->control($field, $options) ?>
                <?php if (!empty($meta['hint'])): ?><p class="field-hint" id="<?= h($field) ?>-hint"><?= h($meta['hint']) ?></p><?php endif ?>
                <?php if ($cascade && $cascade['child'] === $field): ?><div class="cascade-status" role="status" aria-live="polite" data-cascade-status></div><button type="button" class="button button-quiet cascade-retry" data-cascade-retry hidden>Reintentar carga</button><?php endif ?>
            </div>
        <?php endforeach ?>
        </div>
        <div class="form-actions"><?= $this->Form->button($editing ? 'Guardar cambios' : 'Crear ' . $resource['singular'], ['class' => 'button button-primary', 'data-save' => true]) ?><?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'button button-secondary']) ?><span class="muted small">Validación de datos en el servidor</span></div>
    <?= $this->Form->end() ?>
</section>
<aside class="form-aside"><span class="aside-mark" aria-hidden="true">↗</span><h2>Datos precisos.<br>Mejores decisiones.</h2><p>Esta información alimenta la planificación de cargas y la lectura del rendimiento en AtletaLab.</p><div class="aside-note"><strong>Antes de guardar</strong><p>Comprueba los parámetros y las relaciones seleccionadas. Los errores se mostrarán junto a cada campo.</p></div></aside>
</div>
