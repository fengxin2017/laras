<?php


namespace App\SwooleEvents;


use Laras\Contracts\Foundation\Application;
use Laras\Server\TcpServer;
use Swoole\Process;

class OnTcpServerStart
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
     * @param TcpServer $tcpServer
     * @param Process $worker
     */
    public function handle(TcpServer $tcpServer, Process $worker)
    {
    }
}