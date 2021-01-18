<?php

namespace App\Crontab\Jobs;

use App\Annotations\Crontab;
use Laras\Crontab\AbstractCrontab;

/**
 * Class TestCrontab
 * @package App\Crontabs\Jobs
 * @Crontab(rule="10 * * * * *")
 */
class TestCrontab extends AbstractCrontab
{
    public function execute()
    {
        var_dump(date('Y-m-d H:i:s', time()));
    }
}