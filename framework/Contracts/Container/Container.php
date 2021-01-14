<?php
namespace MoneyMaker\Contracts\Container;
use Closure;
use Illuminate\Contracts\Container\Container as IlluminateContainer;

interface Container extends IlluminateContainer
{
    public function coBind(string $abstract, $concrete, Closure $defer = null, array $coRebiningParams = []);

    public function coMake(string $abstract, array $params = []);
}