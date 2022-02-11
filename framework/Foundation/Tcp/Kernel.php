<?php


namespace Laras\Foundation\Tcp;

use App\Http\Controllers\TcpController;
use Illuminate\Container\Util;
use Laras\Contracts\Foundation\Application;
use Laras\Contracts\Tcp\Kernel as KernelContract;
use Laras\Facades\Log;
use Laras\Tcp\Request as TcpReqeust;
use ReflectionClass;
use ReflectionException;
use Swoole\Coroutine\Server\Connection;
use Throwable;

class Kernel implements KernelContract
{

    /**
     * @var Application $app
     */
    protected $app;

    /**
     * Kernel constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param Connection $connection
     * @param $requestRaw
     * @throws ReflectionException
     */
    public function handle(Connection $connection, $requestRaw)
    {
        $tcpRequest = new TcpReqeust($connection, $requestRaw);
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