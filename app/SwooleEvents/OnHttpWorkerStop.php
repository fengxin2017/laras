<?php

namespace App\SwooleEvents;

use Swoole\Process;
use Swoole\Process\Pool;

class OnHttpWorkerStop
{
    public static function handle(Pool $pool, Process $worker)
    {
        var_dump('worker stop');
    }
}