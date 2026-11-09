<?php /** @var \App\View\AppView $this */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso de administradores · AtletaLab</title>
    <?= $this->Html->meta('icon', 'img/mark.svg', ['type' => 'image/svg+xml']) ?>
    <?= $this->Html->css('atletalab') ?>
    <?= $this->Html->script('admin', ['defer' => true]) ?>
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-story">
        <div class="brand brand-light"><?= $this->Html->image('mark.svg', ['alt' => '', 'width' => 44, 'height' => 44]) ?><span>AtletaLab<small>PLANIFICACIÓN DEL RENDIMIENTO</small></span></div>
        <div><span class="eyebrow">CIENCIA + PROGRESIÓN</span><h1>Cada repetición<br>tiene un propósito.</h1><p>La información correcta para planificar la carga, entender la fatiga y construir el siguiente avance.</p></div>
        <div class="login-principles"><span>01 <strong>Planifica</strong></span><span>02 <strong>Evalúa</strong></span><span>03 <strong>Progresa</strong></span></div>
    </section>
    <section class="login-card" aria-label="Acceso al panel">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </section>
</main>
</body>
</html>
