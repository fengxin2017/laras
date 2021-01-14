<?php

namespace MoneyMaker\Router;

use App\Annotations\Middleware;
use Exception;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use Illuminate\Container\Util;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use MoneyMaker\Annotation\AnnotationCollector;
use MoneyMaker\Foundation\Application;
use MoneyMaker\Http\Request;
use MoneyMaker\Http\Response;
use MoneyMaker\Pipe\Pipeline;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Swoole\Coroutine\Channel;
use Throwable;


/**
 * Class Router
 * @package MoneyMaker\Router
 *
 * @method static void addRoute($httpMethod, string $route, $handler, array $options = [])
 * @method static void addGroup($prefix, callable $callback, array $options = [])
 * @method static void get($route, $handler, array $options = [])
 * @method static void post($route, $handler, array $options = [])
 * @method static void put($route, $handler, array $options = [])
 * @method static void delete($route, $handler, array $options = [])
 * @method static void patch($route, $handler, array $options = [])
 * @method static void head($route, $handler, array $options = [])
 */
class Router
{
    /**
     * @var Application $app
     */
    protected $app;

    /**
     * @var RouteCollector $routeCollector
     */
    protected $routeCollector;

    /**
     * @var $controllerPoolCreatedNum
     */
    protected $controllerPoolCreatedNum;

    /**
     * @var $controllerMethodParams
     */
    protected $controllerMethodParams;

    /**
     * cache middleware
     *
     * @var $minifestMiddleware
     */
    protected $minifestMiddleware;

    /**
     * @var bool
     */
    protected $enableControllerPool;

    /**
     * @var int $maxPoolNum
     */
    protected $maxPoolNum;

    /**
     * @var float $controllerPoolWaitTime
     */
    protected $controllerPoolWaitTime = 5.0;

    /**
     * @var array $controllerPool
     */
    protected $controllerPool;

    /**
     * Router constructor.
     * @param Application $app
     * @param RouteCollector $routeCollector
     */
    public function __construct(Application $app, RouteCollector $routeCollector)
    {
        $this->app = $app;
        $this->routeCollector = $routeCollector;
        $this->enableControllerPool = $this->app['config']['controller']['enable_pool'] ?? false;
        $this->maxPoolNum = $this->app['config']['controller.pool_number'];
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     * @throws BindingResolutionException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function dispatch(Request $request, Response $response)
    {
        $httpMethod = $request->method();

        $handler = $this->app->make(GroupCountBased::class)->dispatch($httpMethod, $request->uri());

        if ($handler[0] != Dispatcher::FOUND) {
            return $handler[0];
        }

        [$class, $method, $inputs] = [$handler[1][0], $handler[1][1], $handler[2]];


        $middleware = $this->getMiddleware($httpMethod, $class, $method);
        if ($this->enableControllerPool) {
            $this->createControllerPool($class);
        }
        $controller = $this->getController($class, $method);
        $controllerMethodParams = $this->getControllerMethodParams($class, $method, $inputs);

        try {
            if (!empty($middleware)) {
                return $this->app->make(Pipeline::class)
                    ->send($request, $response)
                    ->through($middleware)
                    ->then(
                        function () use ($controller, $method, $controllerMethodParams) {
                            return call_user_func([$controller, $method], ...$controllerMethodParams);
                        }
                    );
            }
            return call_user_func([$controller, $method], ...$controllerMethodParams);
        } catch (Throwable $throwable) {
            throw $throwable;
        } finally {
            if ($this->enableControllerPool) {
                $this->controllerPool[$class]->push($controller);
            }
        }
    }

    /**
     * @param string $class
     */
    protected function createControllerPool(string $class)
    {
        if (!isset($this->controllerPool[$class])) {
            $this->controllerPool[$class] = new Channel($this->maxPoolNum);
            $this->controllerPoolCreatedNum[$class] = 0;
        }
    }

    /**
     * @param string $class
     * @param string $method
     * @return mixed
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    protected function getController(string $class, string $method)
    {
        if ($this->enableControllerPool) {
            $channel = $this->controllerPool[$class];
            $createdNum = $this->controllerPoolCreatedNum[$class];

            if (!isset($this->controllerMethodParams[$class][$method])) {
                $this->controllerMethodParams[$class][$method] =
                    (new ReflectionMethod($class, $method))->getParameters();
            }

            if ($channel->isEmpty() && $createdNum < $this->maxPoolNum) {
                $this->controllerPoolCreatedNum[$class]++;
                return $this->app->make($class);
            }
            return $channel->pop($this->controllerPoolWaitTime);
        }
        return $this->app->make($class);
    }

    /**
     * @param string $httpMethod
     * @param string $class
     * @param string $method
     * @return array
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    protected function getMiddleware(string $httpMethod, string $class, string $method)
    {
        $methodControllerAction = $httpMethod . '@' . $class . '@' . $method;

        if (isset($this->minifestMiddleware[$methodControllerAction])) {
            $middleware = $this->minifestMiddleware[$methodControllerAction];
        } else {
            if (isset($this->routeCollector->middleware[$methodControllerAction])) {
                $middleware = $this->routeCollector->middleware[$methodControllerAction];
            } else {
                $middleware = [];
            }

            $methodAnnotations = $this->app->make(AnnotationCollector::class)->getAnnotations()['m'];

            if (isset($methodAnnotations[$class][$method])) {
                $annotations = $methodAnnotations[$class][$method];
                foreach ($annotations as $annotation) {
                    if ($annotation instanceof Middleware) {
                        $middleware = array_merge($middleware, Arr::wrap($annotation->middlewares['value']));
                    }
                }
            }

            $this->minifestMiddleware[$methodControllerAction] = $middleware;
        }

        return $middleware;
    }

    /**
     * @param string $class
     * @param string $method
     * @param $inputs
     * @return array
     * @throws Exception
     */
    protected function getControllerMethodParams(string $class, string $method, $inputs)
    {
        $params = [];

        if ($this->enableControllerPool) {
            foreach ($this->controllerMethodParams[$class][$method] as $parameter) {
                /**@var ReflectionParameter $parameter */
                $name = $parameter->getName();
                if (in_array($name, array_keys($inputs))) {
                    $params[] = $inputs[$name];
                } else {
                    $params[] = $this->app->coMake(
                        Util::getParameterClassName($parameter)
                    );
                }
            }
        } else {
            $parameters = (new ReflectionMethod($class, $method))->getParameters();
            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                if (in_array($name, array_keys($inputs))) {
                    $params[] = $inputs[$name];
                } else {
                    $params[] = $this->app->coMake(
                        Util::getParameterClassName($parameter)
                    );
                }
            }
        }

        return $params;
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public static function __callStatic(string $method, array $parameters)
    {
        $collector = Application::getInstance()->make(RouteCollector::class);
        return call_user_func_array([$collector, $method], $parameters);
    }
}