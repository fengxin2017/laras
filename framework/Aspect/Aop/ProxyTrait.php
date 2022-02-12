<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Laras\Aspect\Aop;

use Closure;
use Laras\Annotation\AnnotationCollector;
use Laras\Aspect\AspectCollector;
use Laras\Foundation\Application;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionException;

trait ProxyTrait
{
    protected static function __proxyCall(
        string $className,
        string $method,
        array $arguments,
        Closure $closure
    ) {
        $proceedingJoinPoint = new ProceedingJoinPoint($closure, $className, $method, $arguments);
        $result = self::handleAround($proceedingJoinPoint);
        unset($proceedingJoinPoint);
        return $result;
    }

    /**
     * @param string $className
     * @param string $method
     * @param array $args
     * @return array
     * @throws ReflectionException
     */
    protected static function __getParamsMap(string $className, string $method, array $args): array
    {
        $map = [
            'keys' => [],
            'order' => [],
        ];
        $reflectMethod = (new \ReflectionClass($className))->getMethod($method);
        $reflectParameters = $reflectMethod->getParameters();
        $leftArgCount = count($args);
        foreach ($reflectParameters as $key => $reflectionParameter) {
            $arg = $reflectionParameter->isVariadic() ? $args : array_shift($args);
            if (! isset($arg) && $leftArgCount <= 0) {
                $arg = $reflectionParameter->getDefaultValue();
            }
            --$leftArgCount;
            $map['keys'][$reflectionParameter->getName()] = $arg;
            $map['order'][] = $reflectionParameter->getName();
        }
        return $map;
    }

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws BindingResolutionException
     */
    protected static function handleAround(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $className = $proceedingJoinPoint->className;
        $methodName = $proceedingJoinPoint->methodName;
        if (! AspectManager::has($className, $methodName)) {
            AspectManager::set($className, $methodName, []);
            $aspects = array_unique(array_merge(static::getClassesAspects($className, $methodName), static::getAnnotationAspects($className, $methodName)));
            $queue = new \SplPriorityQueue();
            foreach ($aspects as $aspect) {
                $queue->insert($aspect, AspectCollector::getPriority($aspect));
            }
            while ($queue->valid()) {
                AspectManager::insert($className, $methodName, $queue->current());
                $queue->next();
            }

            unset($annotationAspects, $aspects, $queue);
        }

        if (empty(AspectManager::get($className, $methodName))) {
            return $proceedingJoinPoint->processOriginalMethod();
        }

        return static::makePipeline()->via('process')
            ->through(AspectManager::get($className, $methodName))
            ->send($proceedingJoinPoint)
            ->then(function (ProceedingJoinPoint $proceedingJoinPoint) {
                return $proceedingJoinPoint->processOriginalMethod();
            });
    }

    /**
     * @return Pipeline
     * @throws BindingResolutionException
     */
    protected static function makePipeline(): Pipeline
    {
        return Application::getInstance()->make(Pipeline::class);
    }

    protected static function getClassesAspects(string $className, string $method): array
    {
        $aspects = AspectCollector::get('classes', []);
        $matchedAspect = [];
        foreach ($aspects as $aspect => $rules) {
            foreach ($rules as $rule) {
                if (Aspect::isMatch($className, $method, $rule)) {
                    $matchedAspect[] = $aspect;
                    break;
                }
            }
        }
        // The matched aspects maybe have duplicate aspect, should unique it when use it.
        return $matchedAspect;
    }

    protected static function getAnnotationAspects(string $className, string $method): array
    {
        $matchedAspect = $annotations = $rules = [];

        $classAnnotations = AnnotationCollector::get($className . '.c', []);
        $methodAnnotations = AnnotationCollector::get($className . '.m.' . $method, []);
        $cAnnotations = [];
        $mAnnotations = [];
        foreach ($classAnnotations as $classAnnotation){
            $cAnnotations[get_class($classAnnotation)] = $classAnnotation;
        }
        foreach ($methodAnnotations as $methodAnnotation){
            $mAnnotations[get_class($methodAnnotation)] = $methodAnnotation;
        }
        $annotations = array_unique(array_merge(array_keys($cAnnotations), array_keys($mAnnotations)));
        if (! $annotations) {
            return $matchedAspect;
        }

        $aspects = AspectCollector::get('annotations', []);
        foreach ($aspects as $aspect => $rules) {
            foreach ($rules as $rule) {
                foreach ($annotations as $annotation) {
                    if (strpos($rule, '*') !== false) {
                        $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $rule);
                        $pattern = "/^{$preg}$/";
                        if (! preg_match($pattern, $annotation)) {
                            continue;
                        }
                    } elseif ($rule !== $annotation) {
                        continue;
                    }
                    $matchedAspect[] = $aspect;
                }
            }
        }
        // The matched aspects maybe have duplicate aspect, should unique it when use it.
        return $matchedAspect;
    }
}
