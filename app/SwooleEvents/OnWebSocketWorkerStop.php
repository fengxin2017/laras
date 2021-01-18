<?php


namespace App\SwooleEvents;


use Swoole\Process;
use Swoole\Process\Pool;

class OnWebSocketWorkerStop
{
    public static function handle(Pool $pool, Process $worker)
    {
    }
}