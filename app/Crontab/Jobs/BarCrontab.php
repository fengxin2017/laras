<?php


namespace App\Crontab\Jobs;


use Laras\Crontab\AbstractCrontab;

class BarCrontab extends AbstractCrontab
{
    public function handle()
    {
        var_dump(date('Y-m-d H:i:s', time()));
    }
}