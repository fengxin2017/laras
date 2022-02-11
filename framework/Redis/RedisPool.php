<?php


namespace Laras\Redis;


use Laras\Facades\Config;
use Laras\Foundation\Application;
use Laras\Pool\Pool;
use Swoole\Coroutine\Channel;

/**
 * Class RedisPool
 * @package Laras\Redis
 */
class RedisPool extends Pool
{
    public $app;

    public $min;

    public $max;

    public $current = 0;

    /**
     * @var Channel $connections
     */
    public $connections = null;

    public $booted = false;

    public static $instance = null;

    /**
     * @param int $min
     * @param int $max
     * @return static
     */
    public static function getInstance(int $min = 100, int $max = 500): self
    {
        if (is_null(static::$instance)) {
            static::$instance = new static(Application::getInstance(), $min, $max);
        }

        return static::$instance;
    }

    public function addConnection()
    {
        $this->current++;

        $connection = new RedisManager(Config::get('cache.redis_pool'));
        $connection->setConnectionResolver(function () use ($connection) {
            $connection->connect();
        });

        $this->connections->push($connection);
    }

    /**
     * @return int
     */
    public function getFreeCount(): int
    {
        return $this->connections->length() - $this->min;
    }

    public function remove(&$connection)
    {
        /**@var RedisManager $connection */
        $this->current--;
        $connection->disconnect();
        unset($connection);
    }
}