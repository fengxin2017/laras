<?php

namespace MoneyMaker\Foundation\Bus;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Fluent;

trait Dispatchable
{
    /**
     * @param array $params
     * @return PendingDispatch
     * @throws BindingResolutionException
     * @throws Exception;
     */
    public static function dispatch($params = [])
    {
        return new PendingDispatch(app()->makeWith(static::class, $params));
    }

    /**
     * @param $boolean
     * @param array $params
     * @return Fluent|PendingDispatch
     * @throws BindingResolutionException
     * @throws Exception
     */
    public static function dispatchIf($boolean, $params = [])
    {
        return $boolean
            ? new PendingDispatch(app()->makeWith(static::class, $params))
            : new Fluent;
    }
}
