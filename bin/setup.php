<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$target = $root . '/config/app_local.php';
if (is_file($target)) {
    fwrite(STDERR, "config/app_local.php ya existe. Se conserva su configuración.\n");
    exit(1);
}
$contents = file_get_contents($root . '/config/app_local.example.php');
$contents = str_replace('REEMPLAZAR_CON_64_CARACTERES_ALEATORIOS', bin2hex(random_bytes(32)), $contents);
if (file_put_contents($target, $contents, LOCK_EX) === false) {
    fwrite(STDERR, "No se pudo crear config/app_local.php.\n");
    exit(1);
}
@chmod($target, 0600);
fwrite(STDOUT, "Configuración local creada con salt aleatorio. Configura MySQL antes de iniciar.\n");
