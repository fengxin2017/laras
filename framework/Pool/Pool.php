<?php


namespace Laras\Pool;

use Laras\Contracts\Foundation\Application;
use Laras\Contracts\Pool\Pool as PoolContract;
use Swoole\Coroutine\Channel;

Abstract class Pool implements PoolContract
{
    /**
     * Pool constructor.
     * @param Application $app
     * @param int $min
     * @param int $max
     */
    protected function __construct(Application $app, int $min, int $max)
    {
        $this->app = $app;
        $this->min = $min;
        $this->max = $max;
        $this->connections = new Channel($max);
    }

    abstract public static function getInstance();

    abstract public function addConnection();

    abstract public function remove(&$connection);

    public function init(): void
    {
        if ($this->booted) {
            return;
        }

        for ($i = 0; $i < $this->min; $i++) {
            $this->addConnection();
        }

        $this->booted = true;
    }

    /**
     * @param int $timeout
     * @return mixed
     */
    public function get($timeout = -1)
    {
        if ($this->current < $this->max && $this->connections->isEmpty()) {
            $this->addConnection();
        }

        return $this->connections->pop($timeout);
    }

    /**
     * @param $connection
     */
    public function put($connection): void
    {
        $this->connections->push($connection);
    }
}