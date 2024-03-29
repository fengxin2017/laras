<?php

namespace Laras\Aspect\Aop;

use SplPriorityQueue;

/**
 * @mixin SplPriorityQueue
 */
class AstVisitorRegistry
{
    /**
     * @var SplPriorityQueue
     */
    protected static $queue;

    /**
     * @var array
     */
    protected static $values = [];

    public static function __callStatic($name, $arguments)
    {
        $queue = static::getQueue();
        if (method_exists($queue, $name)) {
            return $queue->{$name}(...$arguments);
        }
        throw new \InvalidArgumentException('Invalid method for ' . __CLASS__);
    }

    public static function insert($value, $priority)
    {
        static::$values[] = $value;
        return static::getQueue()
                     ->insert($value, $priority);
    }

    public static function exists($value): bool
    {
        return in_array($value, static::$values);
    }

    public static function getQueue(): SplPriorityQueue
    {
        if (!static::$queue instanceof SplPriorityQueue) {
            static::$queue = new SplPriorityQueue();
        }
        return static::$queue;
    }
}
