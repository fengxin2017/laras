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
use Exception;
use Laras\Annotation\AnnotationCollector;
use ReflectionException;

class ProceedingJoinPoint
{
    /**
     * @var string
     */
    public $className;

    /**
     * @var string
     */
    public $methodName;

    /**
     * @var mixed[]
     */
    public $arguments;

    /**
     * @var mixed
     */
    public $result;

    /**
     * @var Closure
     */
    public $originalMethod;

    /**
     * @var null|Closure
     */
    public $pipe;

    public function __construct(Closure $originalMethod, string $className, string $methodName, array $arguments)
    {
        $this->originalMethod = $originalMethod;
        $this->className = $className;
        $this->methodName = $methodName;
        $this->arguments = $arguments;
    }

    /**
     * Delegate to the next aspect.
     * @return mixed
     * @throws Exception
     */
    public function process()
    {
        $closure = $this->pipe;
        if (!$closure instanceof Closure) {
            throw new Exception('The pipe is not instanceof \Closure');
        }

        return $closure($this);
    }

    /**
     * Process the original method, this method should trigger by pipeline.
     */
    public function processOriginalMethod()
    {
        $this->pipe = null;
        $closure = $this->originalMethod;
        if (count($this->arguments['keys']) > 1) {
            $arguments = $this->getArguments();
        } else {
            $arguments = array_values($this->arguments['keys']);
        }
        return $closure(...$arguments);
    }

    public function getAnnotationMetadata(): AnnotationMetadata
    {
        $metadata = AnnotationCollector::get($this->className);
        return new AnnotationMetadata($metadata['c'] ?? [], $metadata['m'][$this->methodName] ?? []);
    }

    public function getArguments()
    {
        return value(function () {
            $result = [];
            foreach ($this->arguments['order'] ?? [] as $order) {
                $result[] = $this->arguments['keys'][$order];
            }
            return $result;
        });
    }

    /**
     * @return object|null
     * @throws ReflectionException
     */
    public function getInstance(): ?object
    {
        $ref = new \ReflectionFunction($this->originalMethod);

        return $ref->getClosureThis();
    }
}
