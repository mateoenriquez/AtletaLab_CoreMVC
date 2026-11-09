<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

putenv('SECURITY_SALT=atletalab-test-salt-isolated-from-production-2026');
putenv('APP_HTTPS=false');
putenv('DEBUG=false');
require dirname(__DIR__) . '/config/bootstrap.php';

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;

// Siempre aislada en memoria: la suite nunca escribe en la base configurada del usuario.
ConnectionManager::setConfig('test', [
    'className' => Connection::class, 'driver' => Sqlite::class, 'database' => ':memory:',
    'cacheMetadata' => false, 'quoteIdentifiers' => true,
]);
ConnectionManager::alias('test', 'default');
Configure::write('App.fullBaseUrl', 'http://localhost');
Configure::write('AtletaLab.https', false);
$sessionIni = Configure::read('Session.ini');
$sessionIni['session.cookie_secure'] = false;
Configure::write('Session.ini', $sessionIni);
Configure::write('Session.defaults', 'php');
Configure::write('Error.errorLevel', E_ALL & ~E_USER_DEPRECATED);
