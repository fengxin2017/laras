<?php

use App\SwooleEvents\OnHttpServerStart;
use App\SwooleEvents\OnHttpWorkerStart;
use App\SwooleEvents\OnHttpWorkerStop;
use App\SwooleEvents\OnTcpServerStart;
use App\SwooleEvents\OnTcpWorkerStart;
use App\SwooleEvents\OnTcpWorkerStop;
use App\SwooleEvents\OnWebSocketServerStart;
use App\SwooleEvents\OnWebSocketWorkerStart;
use App\SwooleEvents\OnWebSocketWorkerStop;
use App\SwooleEvents\OnHandShake;


return [

    'tcp' => [
        'worker_number' => 2,
        'listen' => '0.0.0.0',
        'port' => 9504,
        'ssl' => false,
        'setting' => [],
        'on_worker_start' => [
            OnTcpWorkerStart::class, 'handle'
        ],
        'on_worker_stop' => [
            OnTcpWorkerStop::class, 'handle'
        ],
        'on_server_start' => [
            OnTcpServerStart::class, 'handle'
        ]
    ],
    'http' => [
        'worker_number' => 2,
        'listen' => '0.0.0.0',
        'port' => 9503,
        'ssl' => false,
        'setting' => [],
        'buffer_output_size' => 2 * 1024 * 1024,
        'on_worker_start' => [
            OnHttpWorkerStart::class, 'handle'
        ],
        'on_worker_stop' => [
            OnHttpWorkerStop::class, 'handle'
        ],
        'on_server_start' => [
            OnHttpServerStart::class, 'handle'
        ]
    ],
    'websocket' => [
        'worker_number' => 2,
        'listen' => '0.0.0.0',
        'port' => 9505,
        'ssl' => false,
        'setting' => [],
        'route_prefix' => 'websocket',
        'on_worker_start' => [
            OnWebsocketWorkerStart::class, 'handle'
        ],
        'on_worker_stop' => [
            OnWebsocketWorkerStop::class, 'handle'
        ],
        'on_server_start' => [
            OnWebSocketServerStart::class, 'handle'
        ],
        'on_hand_shake' => [
            OnHandShake::class,'handle'
        ]
    ],
];
