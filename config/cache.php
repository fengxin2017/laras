<?php

use Laras\Redis\RedisManager;

return [
    'driver' => RedisManager::class,
    'redis_pool' => [
        'min' => 300,
        'max' => 500,
        'idletime' => 60,
        'host' => 'localhost',
        'port' => '6379',
        'auth' => ''
    ]
];