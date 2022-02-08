<?php


namespace App\Aspects;

use Laras\Aspect\Aop\AbstractAspect;
use Laras\Aspect\Aop\ProceedingJoinPoint;

/**
 * Class FooAspect
 */
class FooAspect extends AbstractAspect
{
    /**
     * @var array
     */
    public $classes = [
    ];

    /**
     * @var array
     */
    public $annotations = [];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
    }
}