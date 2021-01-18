<?php

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Translation\Translator;
use Laras\Facades\DB;
use Laras\Foundation\Application;
use Swoole\Coroutine;

if (!function_exists('wrap')) {
    /**
     * @param Closure $closure
     * @param float|null $timeout
     * @return mixed
     */
    function wrap(Closure $closure, ?float $timeout = null)
    {
        return DB::wrap($closure, $timeout = null);
    }
}

if (!function_exists('app')) {
    /**
     * @param null $abstract
     * @param array $parameters
     * @return mixed|Application|object|void
     * @throws Exception
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Application::getInstance();
        }

        return Application::getInstance()->coMake($abstract, $parameters);
    }
}

if (!function_exists('event')) {
    /**
     * @param mixed ...$args
     * @return mixed
     * @throws Exception
     */
    function event(...$args)
    {
        return app('events')->dispatch(...$args);
    }
}

if (!function_exists('trans')) {
    /**
     * @param null $key
     * @param array $replace
     * @param null $locale
     * @return array|Translator|string
     * @throws Exception
     */
    function trans($key = null, $replace = [], $locale = null)
    {
        if (is_null($key)) {
            /**@var Translator $translator */
            $translator = app('translator');
            return $translator;
        }

        /**@var Translator $translator */
        $translator = app('translator');

        return $translator->get($key, $replace, $locale);
    }
}

if (!function_exists('__')) {
    /**
     * @param null $key
     * @param array $replace
     * @param null $locale
     * @return mixed|Application|object|void|null
     * @throws Exception
     */
    function __($key = null, $replace = [], $locale = null)
    {
        if (is_null($key)) {
            return $key;
        }

        return trans($key, $replace, $locale);
    }
}

if (!function_exists('app_path')) {
    /**
     * @param string $path
     * @return mixed
     * @throws Exception
     */
    function app_path($path = '')
    {
        return app()->path($path);
    }
}

if (!function_exists('storage_path')) {
    /**
     * @param string $path
     * @return string
     * @throws Exception
     */
    function storage_path($path = '')
    {
        return app('path.storage') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('resource_path')) {
    /**
     * @param string $path
     * @return string
     * @throws Exception
     */
    function resource_path($path = '')
    {
        return app()->resourcePath($path);
    }
}

if (!function_exists('view')) {
    /**
     * @param null $view
     * @param array $data
     * @param array $mergeData
     * @return Factory|View|mixed|Application|object|void
     * @throws Exception
     */
    function view($view = null, $data = [], $mergeData = [])
    {
        /**@var Factory $factory */
        $factory = app(Factory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($view, $data, $mergeData);
    }
}

if (!function_exists('config')) {
    /**
     * @param null $key
     * @param null $default
     * @return Repository|mixed|void
     * @throws Exception
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            /**@var Repository $config */
            $config = app('config');
            return $config;
        }

        if (is_array($key)) {
            /**@var Repository $config */
            $config = app('config');
            $config->set($key);
            return;
        }

        /**@var Repository $config */
        $config = app('config');

        return $config->get($key, $default);
    }
}

if (!function_exists('go')) {
    /**
     * @param Closure $closure
     */
    function go(Closure $closure)
    {
        Coroutine::create($closure);
    }
}


