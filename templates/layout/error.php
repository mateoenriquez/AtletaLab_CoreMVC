<!DOCTYPE html>
<html lang="es">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>No se pudo completar la solicitud · AtletaLab</title>
    <?= $this->Html->css('atletalab') ?>
</head>
<body><main class="error-page card"><?= $this->fetch('content') ?><p><?= $this->Html->link('Volver al panel', ['controller' => 'Dashboard', 'action' => 'index'], ['class' => 'button button-primary']) ?></p></main></body>
</html>
