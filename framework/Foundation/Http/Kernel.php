<?php


namespace Laras\Foundation\Http;

use App\Exceptions\ExceptionHandler;
use Closure;
use Exception;
use FastRoute\Dispatcher;
use Fig\Http\Message\StatusCodeInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\View\View;
use Laras\Contracts\Foundation\Application;
use Laras\Contracts\Http\Kernel as KernelContract;
use Laras\Facades\Log;
use Laras\Http\Request;
use Laras\Http\Response;
use Laras\Http\Pipeline;
use Laras\Router\Router;
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
     * @var array $middleware
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
        $larasRequest = new Request($swooleRequest);
        $larasResponse = new Response($swooleResponse);
        $this->bindRequest($larasRequest);
        $this->bindResponse($larasResponse);

        try {
            $response = $this->app->make(Pipeline::class)
                ->send($larasRequest, $larasResponse)
                ->through($this->middleware)
                ->then($this->dispatchToRouter());

            $larasResponse->setChunkLimit($this->app['config']['server.http.buffer_output_size']);

            if ($response === Dispatcher::METHOD_NOT_ALLOWED) {
                $larasResponse->setStatus(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
                $larasResponse->setContent('METHOD NOT ALLOWED!');
                $larasResponse->send();
                return;
            }

            if ($response === Dispatcher::NOT_FOUND) {
                $larasResponse->setStatus(StatusCodeInterface::STATUS_NOT_FOUND);
                $larasResponse->setContent('ROUTE NOT FOUND!');
                $larasResponse->send();
                return;
            }

            if ($response instanceof Response) {
                $response->send();
                return;
            }

            if ($response instanceof View) {
                $larasResponse->setHeader('Content-type', 'text/html');
            }

            if ($response instanceof StreamedResponse) {
                $larasResponse->handleStreamedResponse($response);
                return;
            }

            if ($response instanceof BinaryFileResponse) {
                $larasResponse->handleBinaryFileResponse($response);
                return;
            }

            $larasResponse->setContent($response);
            $larasResponse->send();
        } catch (Throwable $throwable) {
            $this->log($throwable);
            /**@var Response $larasResponse */
            $larasResponse = $this->app->make(ExceptionHandler::class)->handle(
                $throwable,
                $larasRequest,
                $larasResponse
            );
            $larasResponse->send();
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
     * @param Request $larasRequest
     */
    public function bindRequest(Request $larasRequest)
    {
        $this->app->coBind(
            Request::class,
            function () use ($larasRequest) {
                return $larasRequest;
            }
        );
    }

    /**
     * @param Response $larasResponse
     */
    public function bindResponse(Response $larasResponse)
    {
        $this->app->coBind(
            Response::class,
            function () use ($larasResponse) {
                return $larasResponse;
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