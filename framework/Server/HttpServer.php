<?php

namespace Laras\Server;

use App\Http\Kernel;
use Exception;
use Laras\Facades\Config;
use Swoole\Coroutine;
use Swoole\Coroutine\Context;
use Swoole\Coroutine\Http\Server;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;


class HttpServer
{
    /**
     * @var Server $swooleServer
     */
    protected $swooleServer;

    /**
     * @var Kernel $kernel
     */
    protected $kernel;

    /**
     * HttpServer constructor.
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        $this->swooleServer = new Server(
            Config::get('server.http.listen'),
            Config::get('server.http.port'),
            Config::get('server.http.ssl'),
            true
        );
    }

    public function configureServer(array $options)
    {
        $this->swooleServer->set($options);
    }

    /**
     * @throws Exception
     */
    public function registerHttpHandler()
    {
        $this->swooleServer->handle('/',
            function (SwooleRequest $request, SwooleResponse $response) {
                Coroutine::create(
                    function () use ($request, $response) {
                        $this->kernel->handle($request, $response);
                    }
                );
            }
        );
    }

    /**
     * @return Context
     */
    public function registerContext()
    {
        return new Context();
    }

    /**
     * Here we go
     */
    public function start()
    {
        $this->swooleServer->start();
    }

    /**
     * Reload Server
     */
    public function reload()
    {
        $this->swooleServer->shutdown();
        $this->swooleServer->start();
    }

    /**
     * @return Server
     */
    public function getSwooleServer()
    {
        return $this->swooleServer;
    }
}