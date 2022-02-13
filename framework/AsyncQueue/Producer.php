<?php


namespace Laras\AsyncQueue;


use Carbon\Carbon;
use Exception;
use Laras\Foundation\Application;
use Laras\Redis\RedisManager;

class Producer
{
    protected $redis;

    /**
     * Producer constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->redis = Application::getInstance()->coMake(RedisManager::class);
    }

    public function produce(string $queue, $job, $delay = 0)
    {
        if ($delay instanceof Carbon) {
            $delay = $delay->diffInSeconds(Carbon::now()->subSecond());
        }

        $this->redis->zAdd('laras:queue:async:' . $queue, Carbon::now()->addSeconds($delay)->timestamp, serialize($job));
    }
}