<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'paths.php';
require CORE_PATH . 'config' . DS . 'bootstrap.php';
require CAKE . 'functions.php';

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ErrorTrap;
use Cake\Error\ExceptionTrap;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Utility\Security;

foreach ([TMP, LOGS, CACHE . 'models', CACHE . 'persistent', CACHE . 'login', TMP . 'sessions', TMP . 'tests'] as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo crear un directorio de ejecución de AtletaLab.');
    }
}

Configure::config('default', new PhpConfig());
Configure::load('app', 'default', false);
if (is_file(CONFIG . 'app_local.php')) {
    Configure::load('app_local', 'default');
}
$salt = (string)Configure::read('Security.salt');
if (strlen($salt) < 32 || str_contains($salt, 'REEMPLAZAR')) {
    throw new RuntimeException('Configura SECURITY_SALT con al menos 32 caracteres aleatorios o ejecuta php bin/setup.php.');
}
if (Configure::read('debug')) {
    Configure::write('Cache._cake_model_.duration', '+2 minutes');
    Configure::write('Cache._cake_translations_.duration', '+2 minutes');
}
date_default_timezone_set(Configure::read('App.defaultTimezone'));
foreach (['datetime', 'timestamp'] as $type) {
    TypeFactory::build($type)->setDatabaseTimezone('UTC');
}
mb_internal_encoding(Configure::read('App.encoding'));
ini_set('intl.default_locale', Configure::read('App.defaultLocale'));
(new ErrorTrap(Configure::read('Error')))->register();
(new ExceptionTrap(Configure::read('Error')))->register();
if (Configure::read('App.fullBaseUrl')) {
    Router::fullBaseUrl(Configure::read('App.fullBaseUrl'));
}
Cache::setConfig(Configure::consume('Cache'));
ConnectionManager::setConfig(Configure::consume('Datasources'));
Log::setConfig(Configure::consume('Log'));
Security::setSalt(Configure::consume('Security.salt'));
