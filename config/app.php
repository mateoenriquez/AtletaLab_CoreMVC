<?php
declare(strict_types=1);

use Cake\Cache\Engine\FileEngine;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Log\Engine\FileLog;
use function Cake\Core\env;

$https = filter_var(env('APP_HTTPS', true), FILTER_VALIDATE_BOOLEAN);

return [
    'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'App' => [
        'namespace' => 'App', 'encoding' => 'UTF-8', 'defaultLocale' => 'es_EC',
        'defaultTimezone' => 'America/Guayaquil', 'base' => false, 'dir' => 'src',
        'webroot' => 'webroot', 'wwwRoot' => WWW_ROOT,
        'fullBaseUrl' => env('APP_FULL_BASE_URL', false),
        'imageBaseUrl' => 'img/', 'cssBaseUrl' => 'css/', 'jsBaseUrl' => 'js/',
        'paths' => ['plugins' => [ROOT . DS . 'plugins' . DS], 'templates' => [ROOT . DS . 'templates' . DS], 'locales' => [RESOURCES . 'locales' . DS]],
    ],
    'Security' => ['salt' => env('SECURITY_SALT', '')],
    'AtletaLab' => ['https' => $https],
    'Asset' => ['cacheTime' => '+1 day'],
    'Cache' => [
        'default' => ['className' => FileEngine::class, 'path' => CACHE . 'persistent' . DS, 'prefix' => 'atletalab_', 'duration' => '+1 hour'],
        '_cake_translations_' => ['className' => FileEngine::class, 'path' => CACHE . 'persistent' . DS, 'prefix' => 'atletalab_i18n_', 'duration' => '+1 year'],
        '_cake_model_' => ['className' => FileEngine::class, 'path' => CACHE . 'models' . DS, 'prefix' => 'atletalab_model_', 'duration' => '+1 year'],
        'login' => ['className' => FileEngine::class, 'path' => CACHE . 'login' . DS, 'prefix' => 'atletalab_login_', 'duration' => '+15 minutes'],
    ],
    'Error' => ['errorLevel' => E_ALL, 'log' => true, 'trace' => true, 'skipLog' => []],
    'Debugger' => ['outputMask' => ['password' => '********', 'SECURITY_SALT' => '********', 'DB_PASSWORD' => '********']],
    'Datasources' => [
        'default' => [
            'className' => Connection::class, 'driver' => Mysql::class, 'persistent' => false,
            'host' => env('DB_HOST', '127.0.0.1'), 'port' => (int)env('DB_PORT', 3306),
            'username' => env('DB_USERNAME', ''), 'password' => env('DB_PASSWORD', ''),
            'database' => env('DB_DATABASE', 'atletalab'), 'encoding' => 'utf8mb4',
            'timezone' => '+00:00', 'cacheMetadata' => true, 'quoteIdentifiers' => true, 'log' => false,
            'url' => env('DATABASE_URL', null),
        ],
    ],
    'Log' => [
        'debug' => ['className' => FileLog::class, 'path' => LOGS, 'file' => 'debug', 'levels' => ['notice', 'info', 'debug']],
        'error' => ['className' => FileLog::class, 'path' => LOGS, 'file' => 'error', 'levels' => ['warning', 'error', 'critical', 'alert', 'emergency']],
    ],
    'Session' => [
        'defaults' => 'cake', 'cookie' => 'atletalab_session', 'timeout' => 30,
        'ini' => ['session.cookie_httponly' => true, 'session.cookie_secure' => $https, 'session.cookie_samesite' => 'Lax', 'session.use_strict_mode' => true, 'session.gc_maxlifetime' => 1800],
    ],
];
