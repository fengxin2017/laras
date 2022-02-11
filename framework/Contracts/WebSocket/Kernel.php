<?php

namespace Laras\Contracts\WebSocket;

use Laras\Foundation\Application;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

Interface Kernel
{
    public function handle(SwooleRequest $request, SwooleResponse $response);

    public function handleWebSocket(SwooleRequest $request, SwooleResponse $response, $frame);

    /**
     * @return Application
     */
    public function getApplication();
}