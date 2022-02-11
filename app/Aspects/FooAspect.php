<?php


namespace App\Aspects;

use Laras\Aspect\Aop\AbstractAspect;
use Laras\Aspect\Aop\ProceedingJoinPoint;
use Laras\Support\Annotation\Aspect;
use Laras\Support\Annotation\Middleware;
use Exception;

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

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws Exception
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        var_dump('aspect before middleware');
        $result = $proceedingJoinPoint->process();
        var_dump('aspect after middleware');
        return $result;
    }
}