<?php

use Illuminate\Database\DatabaseManager as IlluminateDatabaseManager;

return [
    'driver' => IlluminateDatabaseManager::class,

    'default' => env('DB_CONNECTION', 'mysql'),
    'fetch' => PDO::FETCH_OBJ,
    'min_connection' => 100,
    'max_connection' => 150,
    'idletime' => 60,
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'port' => env('DB_PORT', '3306'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'database' => env('DB_DATABASE', 'laras'),
            'username' => env('DB_USERNAME', 'homestead'),
            'password' => env('DB_PASSWORD', 'secret'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]
    ],
];