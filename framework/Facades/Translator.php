<?php


namespace MoneyMaker\Facades;

/**
 * Class Translator
 * @package MoneyMaker\Facades
 * @method static get($key, array $replace = [], $locale = null, $fallback = true)
 * @method static choice($key, $number, array $replace = [], $locale = null)
 */
class Translator extends Facade
{
    /**
     * @return bool|string
     */
    public function getAccessor()
    {
        return 'translator';
    }
}