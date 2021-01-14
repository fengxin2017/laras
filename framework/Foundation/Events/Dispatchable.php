<?php

namespace MoneyMaker\Foundation\Events;

use Exception;

trait Dispatchable
{
    /**
     * @return mixed
     * @throws Exception
     */
    public static function dispatch()
    {
        return event(new static(...func_get_args()));
    }

    /**
     * @param $boolean
     * @param mixed ...$arguments
     * @return mixed
     * @throws Exception
     */
    public static function dispatchIf($boolean, ...$arguments)
    {
        if ($boolean) {
            return event(new static(...$arguments));
        }

        return null;
    }

    /**
     * @param $boolean
     * @param mixed ...$arguments
     * @return mixed
     * @throws Exception
     */
    public static function dispatchUnless($boolean, ...$arguments)
    {
        if (!$boolean) {
            return event(new static(...$arguments));
        }

        return null;
    }
}
