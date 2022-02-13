<?php
return [
    \Laras\Crontab\CrontabProcess::class, // 定时任务生产进程
    \Laras\AsyncQueue\ConsumerProcess::class, // redis任务消费进程
];