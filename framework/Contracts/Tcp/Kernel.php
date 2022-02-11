<?php

namespace Laras\Contracts\Tcp;

use Laras\Foundation\Application;
use Swoole\Coroutine\Server\Connection;

Interface Kernel
{
    public function handle(Connection $connection, $requestRaw);

    /**
     * @return Application
     */
    public function getApplication();
}