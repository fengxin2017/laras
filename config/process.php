<?php
use Laras\Crontab\CrontabProcess;
use Laras\AsyncQueue\ConsumerProcess;

return [
    CrontabProcess::class, // 定时任务生产进程
    ConsumerProcess::class, // redis任务消费进程
];