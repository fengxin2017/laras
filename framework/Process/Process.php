<?php


namespace Laras\Process;


use Laras\Foundation\Application;

abstract class Process
{
    /**
     * @var Application
     */
    protected $app;

    public function setApp(Application $application)
    {
        $this->app = $application;
    }

    abstract public function process();
}