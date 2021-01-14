<?php

namespace App\Crontab;

use App\Crontab\Jobs\BarCrontab;
use App\Crontab\Jobs\FooCrontab;
use App\Crontab\Jobs\TestCrontab;
use App\Models\User;
use MoneyMaker\Crontab\Kernel as CrontabKernel;

class Kernel extends CrontabKernel
{
    public function schedule()
    {
//        $this->job(TestCrontab::class);
//        $this->job(BarCrontab::class)->minutelyAt(8)->hourlyAt(4)->dailyAt(12);
//        $this->job(FooCrontab::class)->minutely();
//        $this->job(TestCrontab::class)->minutely();
//        $this->job(TestCrontab::class)->daily();
    }
}