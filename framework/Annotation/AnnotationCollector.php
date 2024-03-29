<?php


namespace Laras\Annotation;

use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;

class AnnotationCollector
{
    protected static $container = [];

    public static function collectClass(ReflectionClass $class, array $annotations = [])
    {
        $className = $class->getName();

        if (isset(self::$container[$className]['c'])) {
            self::$container[$className]['c'] = array_merge(self::$container[$className]['c'], $annotations);
        } else {
            self::$container[$className]['c'] = $annotations;
        }
    }

    public static function collectMethod(ReflectionMethod $method, array $annotations = [])
    {
        $className = $method->getDeclaringClass()
            ->getName();
        $methodName = $method->getName();

        if (isset(self::$container[$className]['m'][$methodName])) {
            foreach ($annotations as $annotation) {
                if (!in_array(get_class($annotation), self::$container['__annotation_classname'][$className]['m'][$methodName])) {
                    self::$container[$className]['p'][$methodName][] = $annotation;
                    self::$container['__annotation_classname'][$className]['m'][$methodName][] = get_class($annotation);
                }
            }
        } else {
            self::$container[$className]['m'][$methodName] = $annotations;
            foreach ($annotations as $annotation) {
                self::$container['__annotation_classname'][$className]['m'][$methodName][] = get_class($annotation);
            }
        }
    }

    public static function collectProperty(ReflectionProperty $property, array $annotations = [])
    {
        $className = $property->getDeclaringClass()
            ->getName();
        $propertyName = $property->getName();

        if (isset(self::$container[$className]['p'][$propertyName])) {
            foreach ($annotations as $annotation) {
                if (!in_array(get_class($annotation), self::$container['__annotation_classname'][$className]['p'])) {
                    self::$container[$className]['p'][$propertyName][] = $annotation;
                    self::$container['__annotation_classname'][$className]['p'][] = get_class($annotation);
                }
            }
        } else {
            self::$container[$className]['p'][$propertyName] = $annotations;
            foreach ($annotations as $annotation) {
                self::$container['__annotation_classname'][$className]['p'][] = get_class($annotation);
            }
        }
    }

    public static function collectInjection(BetterReflectionProperty $property, $injection)
    {
        $className = $property->getDeclaringClass()
            ->getName();
        $propertyName = $property->getName();

        self::$container[$className]['i'][$propertyName] = $injection;
    }

    /**
     * @param array $containerData
     */
    public static function setContainer(array $containerData)
    {
        static::$container = $containerData;
    }

    /**
     * @return array
     */
    public static function getContainer()
    {
        return static::$container;
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

    public static function clear()
    {
        static::$container = [];
    }
}