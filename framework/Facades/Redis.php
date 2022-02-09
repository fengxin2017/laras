<?php


namespace Laras\Facades;


use Laras\Redis\RedisManager;
use Redis as NativeRedis;

/**
 * Class Redis
 * @package Laras\Facades
 * @method static get($key)
 * @method static set($key, $value, array $timeout = null)
 * @method static incr($key)
 * @method static expire($key, $ttl)
 * @method static del($key)
 * @method static bool exists($key)
 * @method static zIncrBy($key, $value, $member)
 * @method static zRevRange($key, $start, $end, $withscore = null)
 * @method static sRandMember($key, $count = 1)
 * @method static bool setnx($key, $value)
 * @method static NativeRedis multi($mode = NativeRedis::MULTI)
 * @method static sAdd($key, ...$value1)
 * @method static sPop(string $key)
 * @method static sAddArray($key, array $values)
 *
 * @mixin NativeRedis
 * @see RedisManager
 */
class Redis extends Facade
{
    /**
     * @return string
     */
    public function getAccessor(): string
    {
        return RedisManager::class;
    }
}