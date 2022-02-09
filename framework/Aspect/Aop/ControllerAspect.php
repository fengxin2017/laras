<?php

namespace Laras\Aspect\Aop;


use Laras\Annotation\Controller;

/**
 * Class InjectAspect
 * @\Laras\Aspect\Annotation\Aspect()
 * @package Laras\Aspect\Aop
 */
class ControllerAspect extends AbstractAspect
{
    public $annotations = [
        Controller::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // Do nothing, just to mark the class should be generate the proxy classs.
        return $proceedingJoinPoint->process();
    }
}
