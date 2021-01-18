<?php


namespace Laras\Redis;


use Closure;
use Exception;
use Laras\Facades\Config;
use Redis;

/**
 * Class RedisManager
 * @package Laras\Redis
 * @mixin Redis
 */
class RedisManager
{
    /**
     * @var float $lastUseTime
     */
    protected $lastUseTime = 0.0;

    /**
     * @var Redis $redis
     */
    protected $redis;

    /**
     * @var array $config
     */
    protected $config;

    /**
     * @var Closure $connectionResolver
     */
    protected $connectionResolver;

    /**
     * RedisManager constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect()
    {
        $this->redis = new Redis();

        $this->redis->connect($this->config['host'], $this->config['port']);

        if (isset($this->config['auth']) && $this->config['auth']) {
            $this->redis->auth($this->config['auth']);
        }
    }

    /**
     * @return Redis
     * @throws Exception
     */
    public function getRedis(): Redis
    {
        if ($this->redis) {
            return $this->redis;
        }

        if (is_null($this->connectionResolver)) {
            throw new Exception('Can\' not found redisResolver');
        }

        call_user_func($this->connectionResolver);

        return $this->redis;
    }

    public function disconnect()
    {
        $this->redis = null;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        $maxIdleTime = Config::get('cache.redis_pool.idletime');

        $now = microtime(true);

        if ($now > $maxIdleTime + $this->lastUseTime) {
            return false;
        }

        $this->lastUseTime = $now;
        return true;
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function setConnectionResolver(Closure $closure): self
    {
        $this->connectionResolver = $closure;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDead(): bool
    {
        return !$this->isActive();
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public function __call(string $method, array $parameters)
    {
        $this->lastUseTime = microtime(true);
        return call_user_func([$this->getRedis(), $method], ...$parameters);
    }
}