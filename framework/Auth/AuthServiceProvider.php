<?php


namespace MoneyMaker\Auth;


use Exception;
use Illuminate\Support\ServiceProvider;
use MoneyMaker\Container\Container;
use MoneyMaker\Foundation\Application;

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