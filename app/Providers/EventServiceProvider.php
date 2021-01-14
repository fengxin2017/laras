<?php

namespace App\Providers;

use App\Events\Foo;
use App\Listeners\FooListener;
use App\Listeners\OnServerStart;
use Illuminate\Events\EventServiceProvider as ServiceProvider;
use MoneyMaker\Foundation\Application;

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
        'onServerStart' => [
            OnServerStart::class
        ],
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
