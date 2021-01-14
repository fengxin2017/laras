<?php


namespace MoneyMaker\Facades;


use Exception;
use MoneyMaker\Foundation\Application;
use Illuminate\Support\Traits\ForwardsCalls;

Abstract class Facade
{
    use ForwardsCalls;

    /**
     * @return bool
     */
    public function getAccessor()
    {
        return false;
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public function __call(string $method, array $parameters)
    {
        $caller = $this->getAccessor();
        if (false === $caller) {
            throw new Exception('Method getAccessor should be implements');
        }
        if (is_string($caller)) {
            $caller = Application::getInstance()->coMake($caller);
        }

        return $this->forwardCallTo($caller, $method, $parameters);
    }

    /**
     * @param $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters)
    {
        return call_user_func([new static(), $method], ...$parameters);
    }
}