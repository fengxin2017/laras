<?php
namespace Laras\Contracts\Http;

use Laras\Foundation\Application;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

Interface Kernel
{
    public function handle(SwooleRequest $request, SwooleResponse $response);

    /**
     * @return Application
     */
    public function getApplication();
}