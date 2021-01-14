<?php

namespace MoneyMaker\Facades;

use Illuminate\Contracts\Config\Repository;

/**
 * Class Config
 * @package MoneyMaker\Facades
 * @method static get($key, $default = null)
 * @method static set($key, $value)
 */
class Config extends Facade
{
    /**
     * @return string
     */
    public function getAccessor(): string
    {
        return Repository::class;
    }
}