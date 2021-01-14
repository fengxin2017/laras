<?php


namespace App\Listeners;


use App\Annotations\Inject;
use App\Test\Bar;
use MoneyMaker\Contracts\Foundation\Application;
use MoneyMaker\Server\HttpServer;
use Swoole\Process;

class OnServerStart
{
    /**
     * @Inject()
     * @var Application $app
     */
    protected $app;

    /**
     * @param HttpServer $httpServer
     * @param Process $worker
     */
    public function handle(HttpServer $httpServer, Process $worker)
    {
//        var_dump($httpServer->getSwooleServer());
        $this->app->bind('bar1', function ($app) {
            return new Bar();
        });
    }
}