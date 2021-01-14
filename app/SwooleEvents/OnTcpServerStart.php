<?php


namespace App\SwooleEvents;


use App\Annotations\Inject;
use MoneyMaker\Contracts\Foundation\Application;
use MoneyMaker\Server\TcpServer;
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