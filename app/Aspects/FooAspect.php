<?php


namespace App\Aspects;

use App\Annotations\Middleware;
use Laras\Aspect\Annotation\Aspect;
use Laras\Aspect\Aop\AbstractAspect;
use Laras\Aspect\Aop\ProceedingJoinPoint;

/**
 * @Aspect()
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
    public $annotations = [
        Middleware::class
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        var_dump('i got u');
        $result = $proceedingJoinPoint->process();
        var_dump('over');
        return $result;
    }
}