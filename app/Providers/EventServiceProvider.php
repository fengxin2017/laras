<?php

namespace App\Providers;

use App\Events\Foo;
use App\Listeners\FooListener;
use Illuminate\Events\EventServiceProvider as ServiceProvider;
use Laras\Foundation\Application;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var Application $app
     */
    protected $app;

    /**
     * @var array $listen
     */
    protected $listen = [
        Foo::class => [
            FooListener::class
        ]
    ];

    /**
     * @var array $subscribers
     */
    protected $subscribers = [

    ];
}
