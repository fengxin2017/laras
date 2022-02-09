<?php

namespace Laras\Aspect\Aop;


use App\Annotations\Inject;

/**
 * Class InjectAspect
 * @\Laras\Aspect\Annotation\Aspect()
 * @package Laras\Aspect\Aop
 */
class InjectAspect extends AbstractAspect
{
    public $annotations = [
        Inject::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // Do nothing, just to mark the class should be generate the proxy classs.
        return $proceedingJoinPoint->process();
    }
}
