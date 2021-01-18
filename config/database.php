<?php

use Illuminate\Database\DatabaseManager as IlluminateDatabaseManager;
use Laras\Database\DatabaseManager;

return [
    'driver' => IlluminateDatabaseManager::class,

    'default' => 'mysql',
    'fetch' => PDO::FETCH_OBJ,
    'min_connection' => 100,
    'max_connection' => 150,
    'idletime' => 60,
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'port' => '3306',
            'host' => '192.168.10.10',
            'database' => 'practice',
            'username' => 'homestead',
            'password' => 'secret',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]
    ],
];