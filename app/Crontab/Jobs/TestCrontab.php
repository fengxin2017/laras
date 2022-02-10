<?php

namespace App\Crontab\Jobs;

use Laras\Crontab\AbstractCrontab;
use Laras\Support\Annotation\Crontab;

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