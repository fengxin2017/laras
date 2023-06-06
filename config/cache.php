<?php

use Laras\Redis\RedisManager;

return [
    'driver' => RedisManager::class,
    'redis_pool' => [
        'min' => 3000,
        'max' => 5000,
        'idletime' => 60,
        'host' => 'localhost',
        'port' => '6379',
        'auth' => '123456'
    ]
];