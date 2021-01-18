<?php


namespace App\SwooleEvents;


use App\Test\Bar;
use Laras\Contracts\Foundation\Application;
use Laras\Server\HttpServer;
use Swoole\Process;

class OnHttpServerStart
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * OnHttpServerStart constructor.
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    /**
     * @param HttpServer $httpServer
     * @param Process $worker
     */
    public function handle(HttpServer $httpServer, Process $worker)
    {
        // var_dump($httpServer->getSwooleServer());
        $this->app->bind('bar1', function ($app) {
            return new Bar();
        });
    }
}