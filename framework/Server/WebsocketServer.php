<?php


namespace MoneyMaker\Server;


use App\Http\Kernel;
use MoneyMaker\Facades\Config;
use MoneyMaker\Facades\Log;
use Swoole\Coroutine;
use Swoole\Coroutine\Context;
use Swoole\Coroutine\Http\Server;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\WebSocket\CloseFrame;

class WebsocketServer
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
     * WebsocketServer constructor.
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        $this->swooleServer = new Server(
            Config::get('server.websocket.listen'),
            Config::get('server.websocket.port'),
            Config::get('server.websocket.ssl'),
            true
        );
    }

    public function configureServer(array $options)
    {
        $this->swooleServer->set($options);
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

    public function registerWebSocketHandler()
    {
        $this->swooleServer->handle(
            '/',
            function (SwooleRequest $request, SwooleResponse $response) {
                if (isset($request->header['upgrade']) && $request->header['upgrade'] === 'websocket') {
                    $response->upgrade();
                    $handShakeHandler = $this->kernel->getApplication(
                        )['config']['server.websocket.on_hand_shake'] ?? [];

                    if (!empty($handShakeHandler)) {
                        call_user_func_array([$handShakeHandler[0], $handShakeHandler[1]], [$request, $response]);
                    }

                    while (true) {
                        $frame = $response->recv();
                        if ($frame === false) {
                            Log::error(swoole_last_error());
                            return;
                        }
                        if (get_class($frame) === CloseFrame::class) {
                            $response->close();
                            return;
                        }
                        Coroutine::create(
                            function () use ($request, $response, $frame) {
                                $this->kernel->handleWebSocket($request, $response, $frame);
                            }
                        );
                    }
                } else {
                    Coroutine::create(
                        function () use ($request, $response) {
                            $this->kernel->handle($request, $response);
                        }
                    );
                }
            }
        );
    }
}