<?php


namespace App\SwooleEvents;


use Laras\Contracts\Foundation\Application;
use Laras\Server\WebsocketServer;
use Swoole\Process;

class OnWebSocketServerStart
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
     * @param WebsocketServer $websocketServer
     * @param Process $worker
     */
    public function handle(WebsocketServer $websocketServer, Process $worker)
    {
        //var_dump('websocket server start');
    }
}