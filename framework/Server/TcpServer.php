<?php


namespace Laras\Server;


use Laras\Facades\Config;
use App\Tcp\TcpKernel;
use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;
use Swoole\Exception;

class TcpServer
{
    /**
     * @var \Swoole\Coroutine\Http\Server $swooleServer
     */
    protected $swooleServer;

    /**
     * @var TcpKernel $kernel
     */
    protected $kernel;

    /**
     * TcpServer constructor.
     * @param TcpKernel $kernel
     * @throws Exception
     */
    public function __construct(TcpKernel $kernel)
    {
        $this->kernel = $kernel;
        $this->swooleServer = new Server(
            Config::get('server.tcp.listen'),
            Config::get('server.tcp.port'),
            Config::get('server.tcp.ssl'),
            true
        );
    }

    public function configureServer(array $options)
    {
        $this->swooleServer->set($options);
    }

    public function registerTcpHandler()
    {
        $this->swooleServer->handle(function (Connection $conn) {
            while (true) {
                $requestData = $conn->recv();
                //发送数据
                $this->kernel->handle($conn, $requestData);
            }
        });
    }

    /**
     * Here we go
     */
    public function start()
    {
        $this->swooleServer->start();
    }
}