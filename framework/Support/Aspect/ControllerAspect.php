<?php

namespace Laras\Support\Aspect;


use Exception;
use Laras\Aspect\Aop\AbstractAspect;
use Laras\Aspect\Aop\ProceedingJoinPoint;
use Laras\Support\Annotation\Aspect;
use Laras\Support\Annotation\Controller;

/**
 * Class ControllerAspect
 * @Aspect()
 * @package Laras\Support\Aspect
 */
class ControllerAspect extends AbstractAspect
{
    public $annotations = [
        Controller::class,
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
