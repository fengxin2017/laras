<?php


namespace Laras\Auth;


use Exception;
use Illuminate\Support\ServiceProvider;
use Laras\Container\Container;
use Laras\Foundation\Application;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var Application $app
     */
    protected $app;


    /**
     * @throws Exception
     */
    public function register()
    {
        Container::getInstance()->coBind(AuthManager::class, function () {
            return new AuthManager();
        });
    }
}