<?php


namespace Laras\Router;


use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\RouteParser\Std;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Laras\Foundation\Application;
use ReflectionException;

class RouterServiceProvider extends ServiceProvider
{
    /**
     * @var Application $app
     */
    protected $app;

    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function register()
    {
        $this->app->instance(RouteCollector::class, new RouteCollector(new Std(), new DataGenerator()));
        $this->app->instance(Router::class, new Router($this->app, $this->app->make(RouteCollector::class)));
        $this->app->alias(Router::class, 'router');
    }

    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function boot()
    {
    }
}