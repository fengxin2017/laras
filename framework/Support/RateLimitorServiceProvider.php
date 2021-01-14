<?php

namespace MoneyMaker\Support;

use Illuminate\Support\ServiceProvider;
use MoneyMaker\Facades\Redis;
use Swoole\Timer;

class RateLimitorServiceProvider extends ServiceProvider
{
    /**
     * @var $millisecond
     */
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
        $this->milliseconds = $this->app['config']['ratelimitor.milliseconds'];
        $this->count = (int)$this->app['config']['ratelimitor.count'];
        $this->capacity = (int)(1000 / $this->milliseconds) * $this->count;
        $this->key = 'maker:capacity';
    }

    public function boot()
    {
        Redis::del($this->key);

        Timer::tick(
            $this->milliseconds,
            function (int $timerId) {
                $times = $this->count;
                while ($times > 0 && Redis::sCard($this->key) < $this->capacity) {
                    Redis::sAdd($this->key, microtime(true) . $times);
                    $times--;
                }
            }
        );
    }
}