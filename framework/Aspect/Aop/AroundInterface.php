<?php

namespace Laras\Aspect\Aop;

interface AroundInterface
{
    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint);
}
