<?php
declare(strict_types=1);

use function Cake\Core\env;

return [
    'debug' => filter_var(env('DEBUG', true), FILTER_VALIDATE_BOOLEAN),
    'Security' => ['salt' => env('SECURITY_SALT', 'REEMPLAZAR_CON_64_CARACTERES_ALEATORIOS')],
    'AtletaLab' => ['https' => filter_var(env('APP_HTTPS', false), FILTER_VALIDATE_BOOLEAN)],
    'App' => ['fullBaseUrl' => env('APP_FULL_BASE_URL', 'http://localhost:8765')],
    'Datasources' => ['default' => [
        'host' => env('DB_HOST', '127.0.0.1'), 'port' => (int)env('DB_PORT', 3306),
        'username' => env('DB_USERNAME', 'root'), 'password' => env('DB_PASSWORD', ''),
        'database' => env('DB_DATABASE', 'atletalab'), 'url' => env('DATABASE_URL', null),
    ]],
    'Session' => ['ini' => ['session.cookie_secure' => filter_var(env('APP_HTTPS', false), FILTER_VALIDATE_BOOLEAN)]],
];
