<?php

use Laras\Redis\RedisManager;

return [
    'driver' => RedisManager::class,
    'redis_pool' => [
        'min' => 300,
        'max' => 500,
        'idletime' => 60,
        'host' => '192.168.10.10',
        'port' => '6379',
        'auth' => ''
    ]
];