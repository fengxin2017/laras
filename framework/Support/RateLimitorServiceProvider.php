<?php

namespace Laras\Support;

use Illuminate\Support\ServiceProvider;
use Laras\Facades\Redis;
use Swoole\Timer;

class RateLimitorServiceProvider extends ServiceProvider
{
    protected $milliseconds;

    /**
     * @var int $count
     */
    protected $count;

    /**
     * @var string $key
     */
    protected $key;

    /**
     * @var int $capacity
     */
    protected $capacity;


    public function register()
    {
        $this->milliseconds = (int)$this->app['config']['ratelimitor.milliseconds'];
        $this->capacity = (int)$this->app['config']['ratelimitor.capacity'];
        $this->key = $this->app['config']['ratelimitor.key'] . ':' . $this->app->getWorkerId();
    }

    public function boot()
    {
        Redis::del($this->key);

        $this->put();

        Timer::tick(
            $this->milliseconds,
            function (int $timerId) {
                $this->put();
            }
        );
    }

    protected function put()
    {
        $times = $this->capacity;
        while ($times > 0 && Redis::sCard($this->key) < $this->capacity) {
            Redis::sAdd($this->key, microtime(true) . $times);
            $times--;
        }
    }
}