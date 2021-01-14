<?php

namespace Illuminate\Events;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Support\ServiceProvider;
use MoneyMaker\Foundation\Application;
use ReflectionException;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var array $listen
     */
    protected $listen = [];

    /**
     * @var array $subscribers
     */
    protected $subscribers = [];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('events', function ($app) {
            return (new Dispatcher($app));
        });

        $this->app->alias('events', DispatcherContract::class);
    }

    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function boot()
    {
        $dispatcher = $this->app->make('events');

        foreach ($this->listen as $event => $listeners) {
            foreach (array_unique($listeners) as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }

        foreach ($this->subscribers as $subscriber) {
            $dispatcher->subscribe($subscriber);
        }
    }
}
