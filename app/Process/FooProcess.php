<?php


namespace App\Process;


use Laras\Process\Process as AbstractProcess;
use Laras\Support\Annotation\Process;
use Swoole\Coroutine;

/**
 * Class FooProcess
 * @package App\Process
 */
class FooProcess extends AbstractProcess
{
    public function process()
    {
        while (true) {
            var_dump('this is a app process');
            Coroutine::sleep(2);
        }
    }
}