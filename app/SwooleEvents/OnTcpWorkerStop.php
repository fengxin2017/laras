<?php


namespace App\SwooleEvents;


use Swoole\Process;
use Swoole\Process\Pool;

class OnTcpWorkerStop
{
    public static function handle(Pool $pool, Process $worker)
    {
    }
}