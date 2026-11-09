<div class="eyebrow">PANEL DE ADMINISTRACIÓN</div>
<h2>Bienvenido a AtletaLab</h2>
<p class="login-intro">Ingresa con tu cuenta de administrador para gestionar la planificación del rendimiento.</p>
<?= $this->Form->create(null, ['novalidate' => true, 'class' => 'login-form']) ?>
    <?= $this->Form->control('email', ['label' => 'Correo electrónico', 'type' => 'email', 'autocomplete' => 'username', 'required' => true, 'placeholder' => 'tu@correo.com']) ?>
    <?= $this->Form->control('password', ['label' => 'Contraseña', 'type' => 'password', 'value' => '', 'autocomplete' => 'current-password', 'required' => true]) ?>
    <?= $this->Form->button('Acceder al panel →', ['class' => 'button button-primary']) ?>
<?= $this->Form->end() ?>
<p class="login-footnote">Acceso exclusivo para administradores autorizados.</p>
