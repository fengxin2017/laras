<?php

namespace App\Http;

use App\Http\Middleware\TrimStrings;
use Laras\Foundation\WebSocket\Kernel;

class WebSocketKernel extends Kernel
{
    /**
     * @var array
     */
    protected $middleware = [
        TrimStrings::class
    ];
}