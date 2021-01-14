<?php

namespace MoneyMaker\Container;

use App\Annotations\Inject;
use Closure;
use Exception;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Container\Util;
use Illuminate\Contracts\Container\BindingResolutionException;
use MoneyMaker\Annotation\AnnotationCollector;
use MoneyMaker\Contracts\Container\Container as MoneyMakerContainerContract;
use ReflectionException;
use ReflectionObject;
use ReflectionParameter;
use Swoole\Coroutine;

/**
 * Class Container
 * @package MoneyMaker\Container
 */
class Container extends IlluminateContainer implements MoneyMakerContainerContract
{
    /**
     * @var array
     */
    protected $contextHandler = [];

    /**
     * @var array
     */
    protected $deferHandler = [];

    /**
     * @var array
     */
    protected $coRebindingHandler = [];

    /**
     * @param ReflectionParameter $parameter
     * @return array|mixed|object|void
     * @throws BindingResolutionException
     * @throws ReflectionException
     * @throws Exception
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $parameter->isVariadic()
                ? $this->resolveVariadicClass($parameter)
                : $this->coMake(Util::getParameterClassName($parameter));
        }

            // If we can not resolve the class instance, we will check to see if the value
            // is optional, and if it is we will return the optional parameter value as
            // the value of the dependency, similarly to how we do this with scalars.
        catch (BindingResolutionException $e) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            if ($parameter->isVariadic()) {
                return [];
            }

            throw $e;
        }
    }

    /**
     * @param string $abstract
     * @param $concrete
     * @param Closure|null $defer
     * @param array $coRebiningParams
     * @throws Exception
     */
    public function coBind(string $abstract, $concrete, Closure $defer = null, array $coRebiningParams = []): void
    {
        if (isset(Coroutine::getContext()[$abstract])) {
            unset(Coroutine::getContext()[$abstract]);
        }

        $this->contextHandler[$abstract] = $concrete;

        if (!is_null($defer)) {
            $this->deferHandler[$abstract] = $defer;
        }

        if (Coroutine::getCid() > 0 && isset($this->coRebindingHandler[$abstract])) {
            $instance = $this->coMake($abstract, $coRebiningParams);
            foreach ($this->coRebindingHandler[$abstract] as $callback) {
                call_user_func($callback, $this, $instance);
            }
        }
    }

    /**
     * @param string $abstract
     * @param array $params
     * @return mixed|object|void
     * @throws Exception
     */
    public function coMake(string $abstract, array $params = [])
    {
        if (isset(Coroutine::getContext()[$abstract])) {
            return Coroutine::getContext()[$abstract];
        }

        if (isset($this->contextHandler[$abstract])) {
            $concrete = call_user_func($this->contextHandler[$abstract], ...$params);

            Coroutine::getContext()[$abstract] = $concrete;
            if (isset($this->deferHandler[$abstract])) {
                Coroutine::defer(
                    function () use ($abstract, $concrete) {
                        $this->deferHandler[$abstract]($concrete);
                    }
                );
            }

            if (is_object($concrete)) {
                return $this->inject($concrete);
            }

            return $concrete;
        }

        return $this->make($abstract, $params);
    }

    /**
     * @param callable|string $abstract
     * @param array $parameters
     * @return mixed
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    public function make($abstract, array $parameters = [])
    {
        $concrete = $this->resolve($abstract, $parameters);
        if (is_object($concrete)) {
            return $this->inject($concrete);
        }

        return $concrete;
    }

    /**
     * @param $concrete
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     */
    protected function inject($concrete)
    {
        $annotationCollector = $this->instances[AnnotationCollector::class] ?? null;

        if (!$annotationCollector || !$class = get_class($concrete)) {
            return $concrete;
        }

        $injectProperties = [];

        // 传参方式注入
        if (isset($annotationCollector->getAnnotations()['p'][$class])) {
            $propertyInjects = $annotationCollector->getAnnotations()['p'][$class];

            if (empty($propertyInjects)) {
                return $concrete;
            }

            $reflectObject = new ReflectionObject($concrete);

            $injected = false;
            foreach ($propertyInjects as $propertyKey => $injects) {
                foreach ($injects as $inject) {
                    if ($inject instanceof Inject) {
                        if (isset($inject->inject['value'])) {
                            $injectClass = is_array($inject->inject['value']) ?
                                current($inject->inject['value']) :
                                $inject->inject['value'];
                            $property = $reflectObject->getProperty($propertyKey);
                            $property->setAccessible(true);
                            $property->setValue($concrete, $this->coMake($injectClass));
                            $injected = true;
                        }
                        $injectProperties[] = $propertyKey;
                    }
                }
            }

            if ($injected) {
                return $concrete;
            }
        }


        // 不传参方式注入，通过 var 方式指定注入对象方式
        if (isset($annotationCollector->getAnnotations()['i'][$class])) {
            $injectClasses = $annotationCollector->getAnnotations()['i'][$class];

            if (empty($injectClasses)) {
                return $concrete;
            }

            $reflectObject = new ReflectionObject($concrete);
            foreach ($injectClasses as $propertyKey => $injectClass) {
                if (in_array($propertyKey, $injectProperties) && (class_exists($injectClass) || interface_exists(
                            $injectClass
                        ))) {
                    $property = $reflectObject->getProperty($propertyKey);
                    $property->setAccessible(true);
                    $property->setValue($concrete, $this->coMake($injectClass));
                    return $concrete;
                }
            }
        }

        return $concrete;
    }
}
