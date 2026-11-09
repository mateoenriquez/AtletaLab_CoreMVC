<?php
/** @var \App\View\AppView $this */
$controller = $this->getRequest()->getParam('controller');
$nav = ['Dashboard' => 'Dashboard', 'Users' => 'Usuarios', 'Roles' => 'Roles', 'Archetypes' => 'Arquetipos', 'Objectives' => 'Objetivos', 'Exercises' => 'Ejercicios', 'Routines' => 'Rutinas', 'RoutineExercises' => 'Ejercicios de rutina', 'WorkoutLogs' => 'Logs de entrenamiento'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($this->fetch('title') ?: 'Administración') ?> · AtletaLab</title>
    <?= $this->Html->meta('icon', 'img/mark.svg', ['type' => 'image/svg+xml']) ?>
    <?= $this->Html->css('atletalab') ?>
    <?= $this->Html->script('admin', ['defer' => true]) ?>
</head>
<body>
<a href="#main" class="skip-link">Saltar al contenido</a>
<header class="site-header">
    <div class="header-main shell">
        <a class="brand" href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index']) ?>">
            <?= $this->Html->image('mark.svg', ['alt' => '', 'width' => 38, 'height' => 38]) ?>
            <span>Atleta<span class="brand-accent">Lab</span><small>PLANIFICACIÓN DEL RENDIMIENTO</small></span>
        </a>
        <div class="header-account">
            <span class="workspace-pill"><span class="status-dot"></span>Administración</span>
            <span class="avatar" aria-hidden="true"><?= h(mb_substr($currentUser->firstName ?? 'A', 0, 1)) ?></span>
            <span class="account-name"><?= h($currentUser->firstName ?? 'Administrador') ?><small>Administrador</small></span>
            <?= $this->Form->create(null, ['url' => ['controller' => 'Users', 'action' => 'logout', '_method' => 'POST'], 'novalidate' => true, 'class' => 'inline-form']) ?>
                <?= $this->Form->button('Salir', ['class' => 'button button-quiet']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
    <nav class="top-nav shell" aria-label="Navegación principal">
        <?php foreach ($nav as $name => $label): ?>
            <?= $this->Html->link($label, ['controller' => $name, 'action' => 'index'], ['class' => $controller === $name ? 'nav-link active' : 'nav-link', 'aria-current' => $controller === $name ? 'page' : null]) ?>
        <?php endforeach ?>
    </nav>
</header>
<main id="main" class="shell page-main">
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>
</main>
<footer class="site-footer shell"><span>AtletaLab · Ciencia aplicada a cada repetición.</span><span>Progresión · Fatiga · Recuperación</span></footer>
</body>
</html>
