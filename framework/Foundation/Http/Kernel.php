<?php


namespace MoneyMaker\Foundation\Http;

use App\Exceptions\ExceptionHandler;
use App\Http\Controllers\TcpController;
use Closure;
use Exception;
use FastRoute\Dispatcher;
use Fig\Http\Message\StatusCodeInterface;
use Illuminate\Container\Util;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\View\View;
use MoneyMaker\Contracts\Foundation\Application;
use MoneyMaker\Contracts\Http\Kernel as KernelContract;
use MoneyMaker\Facades\Log;
use MoneyMaker\Http\Request;
use MoneyMaker\Http\Response;
use MoneyMaker\Pipe\Pipeline;
use MoneyMaker\Router\Router;
use MoneyMaker\Tcp\Request as TcpReqeust;
use MoneyMaker\WebSocket\Request as WebSocketRequest;
use MoneyMaker\WebSocket\Response as WebSocketResponse;
use ReflectionClass;
use ReflectionException;
use Swoole\Coroutine\Server\Connection;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class Kernel implements KernelContract
{

    /**
     * @var Application $app
     */
    protected $app;

    /**
     * The application's middleware stack.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Kernel constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param SwooleRequest $swooleRequest
     * @param SwooleResponse $swooleResponse
     * @return bool|void
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function handle(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse)
    {
        $moneyMakerRequest = new Request($swooleRequest);
        $moneyMakerResponse = new Response($swooleResponse);
        $this->bindRequest($moneyMakerRequest);
        $this->bindResponse($moneyMakerResponse);

        try {
            $response = $this->app->make(Pipeline::class)
                ->send($moneyMakerRequest, $moneyMakerResponse)
                ->through($this->middleware)
                ->then($this->dispatchToRouter());

            $moneyMakerResponse->setChunkLimit($this->app['config']['server.http.buffer_output_size']);

            if ($response === Dispatcher::METHOD_NOT_ALLOWED) {
                $moneyMakerResponse->setStatus(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
                $moneyMakerResponse->setContent('METHOD NOT ALLOWED!');
                $moneyMakerResponse->send();
                return;
            }

            if ($response === Dispatcher::NOT_FOUND) {
                $moneyMakerResponse->setStatus(StatusCodeInterface::STATUS_NOT_FOUND);
                $moneyMakerResponse->setContent('ROUTE NOT FOUND!');
                $moneyMakerResponse->send();
                return;
            }

            if ($response instanceof Response) {
                $response->send();
                return;
            }

            if ($response instanceof View) {
                $moneyMakerResponse->setHeader('Content-type', 'text/html');
            }

            if ($response instanceof StreamedResponse) {
                $moneyMakerResponse->handleStreamedResponse($response);
                return;
            }

            if ($response instanceof BinaryFileResponse) {
                $moneyMakerResponse->handleBinaryFileResponse($response);
                return;
            }

            $moneyMakerResponse->setContent($response);
            $moneyMakerResponse->send();
        } catch (Throwable $throwable) {
            $this->log($throwable);
            /**@var Response $moneyMakerResponse */
            $moneyMakerResponse = $this->app->make(ExceptionHandler::class)->handle(
                $throwable,
                $moneyMakerRequest,
                $moneyMakerResponse
            );
            $moneyMakerResponse->send();
        }
    }

    /**
     * @return Closure
     */
    protected function dispatchToRouter()
    {
        return function ($request, $response) {
            return $this->app->make(Router::class)
                ->dispatch($request, $response);
        };
    }

    /**
     * @param Request $moneyMakerRequest
     */
    public function bindRequest(Request $moneyMakerRequest)
    {
        $this->app->coBind(
            Request::class,
            function () use ($moneyMakerRequest) {
                return $moneyMakerRequest;
            }
        );
    }

    /**
     * @param Response $moneyMakerResponse
     */
    public function bindResponse(Response $moneyMakerResponse)
    {
        $this->app->coBind(
            Response::class,
            function () use ($moneyMakerResponse) {
                return $moneyMakerResponse;
            }
        );
    }

    /**
     * @param Connection $connection
     * @param $requestData
     * @throws ReflectionException
     */
    public function handleTcp(Connection $connection, $requestData)
    {
        $tcpRequest = new TcpReqeust($connection, $requestData);
        $this->bindTcpResquest($tcpRequest);

        $constructorParams = $handlerParams = [];

        $reflectController = new ReflectionClass(TcpController::class);
        $reflectConstructor = $reflectController->getConstructor();
        $parameters = $reflectController->getMethod('handle')->getParameters();

        if (is_null($reflectConstructor)) {
            $tcpController = new TcpController();
        } else {
            $constructorParameters = $reflectConstructor->getParameters();
            foreach ($constructorParameters as $constructorParameter) {
                $constructorParams[] = $this->app->coMake(
                    Util::getParameterClassName($constructorParameter)
                );
            }
            $tcpController = $reflectController->newInstanceArgs($constructorParams);
        }

        foreach ($parameters as $parameter) {
            $handlerParams[] = $this->app->coMake(
                Util::getParameterClassName($parameter)
            );
        }

        call_user_func_array([$tcpController, 'handle'], $handlerParams);
    }

    /**
     * @param TcpReqeust $tcpRequest
     */
    public function bindTcpResquest(TcpReqeust $tcpRequest)
    {
        $this->app->coBind(
            TcpReqeust::class,
            function () use ($tcpRequest) {
                return $tcpRequest;
            }
        );
    }

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
        $this->app->make(Pipeline::class)
            ->send($webSocketRequest, $webSocketResponse)
            ->through($this->middleware)
            ->then($this->dispatchToRouter());
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