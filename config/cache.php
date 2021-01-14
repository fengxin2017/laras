<?php

use MoneyMaker\Redis\RedisManager;

return [
    'driver' => RedisManager::class,
    'redis_pool' => [
        'min' => 300,
        'max' => 500,
        'idletime' => 60,
        'host' => '192.168.20.10',
        'port' => '6379',
        'auth' => ''
    ]
];