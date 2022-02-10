<?php

namespace Laras\Support\Aspect;


use Exception;
use Laras\Aspect\Aop\AbstractAspect;
use Laras\Aspect\Aop\ProceedingJoinPoint;
use Laras\Support\Annotation\Aspect;
use Laras\Support\Annotation\Inject;

/**
 * Class InjectAspect
 * @Aspect()
 * @package Laras\Support\Aspect
 */
class InjectAspect extends AbstractAspect
{
    public $annotations = [
        Inject::class,
    ];

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws Exception
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        return $proceedingJoinPoint->process();
    }
}
