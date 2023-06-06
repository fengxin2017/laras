<?php

namespace Laras\Router;

use Exception;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use Illuminate\Container\Util;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Laras\Annotation\AnnotationCollector;
use Laras\Foundation\Application;
use Laras\Http\Pipeline;
use Laras\Http\Request;
use Laras\Http\Response;
use Laras\Support\Annotation\Inject;
use Laras\Support\Annotation\Middleware;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Swoole\Coroutine\Channel;
use Throwable;


/**
 * Class Router
 * @package Laras\Router
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

    protected $controllerPoolCreatedNum;

    protected $controllerMethodParams;

    protected $minifestMiddleware;

    /**
     * @var bool $enableControllerPool
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

        $handler = $this->app->make(GroupCountBased::class)
            ->dispatch($httpMethod, $request->uri());

        if ($handler[0] != Dispatcher::FOUND) {
            return $handler[0];
        }

        [$class, $method, $inputs] = [$handler[1][0], $handler[1][1], $handler[2]];


        $middleware = $this->getMiddleware($httpMethod, $class, $method);

        //  if controller has inject property we should not create controller pool cause the controller
        //  maybe inject instance like "Request::class"
        if ($this->shouldCreateControllerPool($class)) {
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
            if ($this->enableControllerPool && isset($this->controllerPool[$class])) {
                $this->controllerPool[$class]->push($controller);
            }
        }
    }

    /**
     * @param string $class
     * @return bool
     * @throws ReflectionException
     */
    protected function shouldCreateControllerPool(string $class)
    {
        if ($this->enableControllerPool) {
            if (isset(AnnotationCollector::get($class)['p'])) {
                $enable = $this->classExistInjectAnnotation(AnnotationCollector::get($class)['p']);
                if (false == $enable) {
                    return false;
                }
            }

            // ensure there is not inject property in parent class
            $reflectClass = new ReflectionClass($class);
            while ($parentReflectClass = $reflectClass->getParentClass()) {
                if (isset(AnnotationCollector::get($parentReflectClass->getName())['p'])) {
                    $enable = $this->classExistInjectAnnotation(AnnotationCollector::get($parentReflectClass->getName())['p']);
                    if (false == $enable) {
                        return false;
                    }
                }

                $reflectClass = $parentReflectClass;
            }

            return true;
        }

        return false;
    }

    /**
     * @param array $annotations
     * @return bool
     */
    protected function classExistInjectAnnotation(array $annotations)
    {
        foreach ($annotations as $propertyAnnotations) {
            foreach ($propertyAnnotations as $propertyAnnotation) {
                if ($propertyAnnotation instanceof Inject) {
                    return false;
                }
            }
        }

        return true;
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
        if ($this->enableControllerPool && isset($this->controllerPool[$class])) {
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

            if (isset(AnnotationCollector::getContainer()[$class]['m'][$method])) {
                $annotations = AnnotationCollector::getContainer()[$class]['m'][$method];
                foreach ($annotations as $annotation) {
                    if ($annotation instanceof Middleware) {
                        $annotationMiddlewares = [];
                        foreach (Arr::wrap($annotation->middlewares['value']) as $key => $annotationMiddlewareOrParams) {
                            if (is_numeric($key)) {
                                // @SomeAnnotation(Foo::class)
                                //  $annotationMiddlewareOrParams will be annotation class
                                $annotationMiddlewares[] = $annotationMiddlewareOrParams;
                            } else {
                                // @SomeAnnotation({Foo::class:"1,3,4"})
                                // $key will be annotation class
                                // $annotationMiddlewareOrParams will be params
                                $annotationMiddlewares[] = $key . ':' . $annotationMiddlewareOrParams;
                            }
                        }

                        $middleware = array_merge($middleware, $annotationMiddlewares);
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
        var_dump($class);
        if ($this->enableControllerPool && isset($this->controllerPool[$class])) {
            $parameters = $this->controllerMethodParams[$class][$method];
        } else {
            $parameters = (new ReflectionMethod($class, $method))->getParameters();
        }

        foreach ($parameters as $parameter) {
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

        return $params;
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws BindingResolutionException
     */
    public static function __callStatic(string $method, array $parameters)
    {
        $collector = Application::getInstance()
            ->make(RouteCollector::class);
        return call_user_func_array([$collector, $method], $parameters);
    }
}