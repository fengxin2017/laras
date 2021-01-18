<?php


namespace Laras\Facades;

/**
 * Class Translator
 * @package Laras\Facades
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