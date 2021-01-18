<?php


namespace App\SwooleEvents;


use Swoole\Process;
use Swoole\Process\Pool;

class OnWebSocketWorkerStart
{
    public static function handle(Pool $pool, Process $worker)
    {
    }
}