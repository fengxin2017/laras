<?php

namespace App\Crontab\Jobs;

use App\Annotations\Crontab;
use Laras\Crontab\AbstractCrontab;

/**
 * Class FooCrontab
 * @package App\Crontabs\Jobs
 * @Crontab(rule="20 0-59/2 * * * *")
 */
class FooCrontab extends AbstractCrontab
{
    public function execute()
    {
        var_dump(date('Y-m-d H:i:s', time()) . '----FOO');
    }
}