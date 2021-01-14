<?php


namespace MoneyMaker\Foundation\Bus;


abstract class Job
{
    use Dispatchable;

    public $delay;

    abstract public function handle();

    /**
     * @param $delay
     */
    public function delay($delay)
    {
        $this->delay = $delay;
    }
}