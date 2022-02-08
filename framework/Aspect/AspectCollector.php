<?php

namespace Laras\Aspect;

use Illuminate\Support\Arr;
use SplPriorityQueue;

class AspectCollector
{
    /**
     * @var array
     */
    protected static $container = [];

    /**
     * @var array
     */
    protected static $aspectRules = [];

    public static function setAround(string $aspect, array $classes, array $annotations, ?int $priority = null): void
    {
        if (! is_int($priority)) {
            $priority = static::getDefaultPriority();
        }
        $setter = function ($key, $value) {
            if (static::has($key)) {
                $value = array_merge(static::get($key, []), $value);
                static::set($key, $value);
            } else {
                static::set($key, $value);
            }
        };
        $setter('classes.' . $aspect, $classes);
        $setter('annotations.' . $aspect, $annotations);
        if (isset(static::$aspectRules[$aspect])) {
            static::$aspectRules[$aspect] = [
                'priority' => $priority,
                'classes' => array_merge(static::$aspectRules[$aspect]['classes'] ?? [], $classes),
                'annotations' => array_merge(static::$aspectRules[$aspect]['annotations'] ?? [], $annotations),
            ];
        } else {
            static::$aspectRules[$aspect] = [
                'priority' => $priority,
                'classes' => $classes,
                'annotations' => $annotations,
            ];
        }
    }

    public static function clear(?string $key = null): void
    {
        if ($key) {
            unset(static::$container['classes'][$key], static::$container['annotations'][$key], static::$aspectRules[$key]);
        } else {
            static::$container = [];
            static::$aspectRules = [];
        }
    }

    public static function getRule(string $aspect): array
    {
        return static::$aspectRules[$aspect] ?? [];
    }

    public static function getPriority(string $aspect): int
    {
        return static::$aspectRules[$aspect]['priority'] ?? static::getDefaultPriority();
    }

    public static function getRules(): array
    {
        return static::$aspectRules;
    }

    public static function getContainer(): array
    {
        return static::$container;
    }

    public static function serialize(): string
    {
        return serialize([static::$aspectRules, static::$container]);
    }

    public static function deserialize(string $metadata): bool
    {
        [$rules, $container] = unserialize($metadata);
        static::$aspectRules = $rules;
        static::$container = $container;
        return true;
    }

    private static function getDefaultPriority(): int
    {
        return 0;
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public static function get(string $key, $default = null)
    {
        return Arr::get(static::$container, $key) ?? $default;
    }

    /**
     * @param string $key
     * @param $value
     */
    public static function set(string $key, $value): void
    {
        Arr::set(static::$container, $key, $value);
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return Arr::has(static::$container, $key);
    }


    public static function list(): array
    {
        return static::$container;
    }
}
