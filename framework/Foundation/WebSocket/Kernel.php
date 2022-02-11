<?php


namespace Laras\Foundation\WebSocket;

use Illuminate\Contracts\Container\BindingResolutionException;
use Laras\Contracts\Foundation\Application;
use Laras\Contracts\WebSocket\Kernel as KernelContract;
use Laras\Facades\Log;
use Laras\Foundation\Http\Kernel as HttpKernel;
use Laras\Pipe\Pipeline;
use Laras\WebSocket\Request as WebSocketRequest;
use Laras\WebSocket\Response as WebSocketResponse;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Throwable;

class Kernel extends HttpKernel implements KernelContract
{
    /**
     * @param SwooleRequest $swooleRequest
     * @param SwooleResponse $swooleResponse
     * @param $frame
     * @throws BindingResolutionException
     */
    public function handleWebSocket(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse, $frame)
    {
        $webSocketRequest = new WebSocketRequest($swooleRequest, $frame);
        $webSocketResponse = new WebSocketResponse($swooleResponse);
        $this->bindWebSocketRequest($webSocketRequest);
        $this->binWebSocketResponse($webSocketResponse);
        $response = $this->app->make(Pipeline::class)
            ->send($webSocketRequest, $webSocketResponse)
            ->through($this->middleware)
            ->then($this->dispatchToRouter());

        if (!is_null($response)) {
            if ($response instanceof WebSocketResponse) {
                $response->push();
            } else {
                $webSocketResponse->setWebSocketResponseData($response);
                $webSocketResponse->push();
            }
        }
    }

    public function bindWebSocketRequest(WebSocketRequest $webSocketRequest)
    {
        $this->app->coBind(
            WebSocketRequest::class,
            function () use ($webSocketRequest) {
                return $webSocketRequest;
            }
        );
    }

    public function binWebSocketResponse(WebSocketResponse $webSocketResponse)
    {
        $this->app->coBind(
            WebSocketResponse::class,
            function () use ($webSocketResponse) {
                return $webSocketResponse;
            }
        );
    }

    /**
     * @param Throwable $throwable
     */
    public function log(Throwable $throwable)
    {
        Log::error(
            $throwable->getMessage(),
            ['exception' => $throwable]
        );
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->app;
    }
}