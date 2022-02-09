<?php


namespace App\Aspects;

use App\Http\Controllers\HttpController;
use Laras\Aspect\Annotation\Aspect;
use Laras\Aspect\Aop\AbstractAspect;
use Laras\Aspect\Aop\ProceedingJoinPoint;

/**
 * Class BarAspect
 * @Aspect()
 * @package App\Aspects
 */
class BarAspect extends AbstractAspect
{
    /**
     * @var array
     */
    public $classes = [
//        HttpController::class
    ];

    /**
     * @var array
     */
    public $annotations = [];

    /**
     * @var null|int
     */
    public $priority;

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws \Exception
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        var_dump('haha i am aspect before');
        $result =  $proceedingJoinPoint->process();
        var_dump('lol i am aspect after');
        return $result;
    }
}