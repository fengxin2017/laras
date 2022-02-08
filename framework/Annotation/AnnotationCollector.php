<?php


namespace Laras\Annotation;

use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;

class AnnotationCollector
{
    protected static $container   = [];
    protected        $annotations = [];
    protected static $instance;

    public function collectClass(ReflectionClass $class, array $annotations = [])
    {
        $className = $class->getName();

        if (isset($this->annotations['c'][$className])) {
            $this->annotations['c'][$className] = array_merge($this->annotations['c'][$className], $annotations);
        } else {
            $this->annotations['c'][$className] = $annotations;
        }
    }

    public function collectMethod(ReflectionMethod $method, array $annotations = [])
    {
        $className  = $method->getDeclaringClass()
                             ->getName();
        $methodName = $method->getName();
        if (isset($this->annotations['m'][$className][$methodName])) {
            $this->annotations['m'][$className][$methodName] = array_merge(
                $this->annotations['m'][$className][$methodName],
                $annotations
            );
        } else {
            $this->annotations['m'][$className][$methodName] = $annotations;
        }
    }

    public function collectProperty(ReflectionProperty $property, array $annotations = [])
    {
        $className    = $property->getDeclaringClass()
                                 ->getName();
        $propertyName = $property->getName();
        if (isset($this->annotations['p'][$className][$propertyName])) {
            $this->annotations['p'][$className][$propertyName] = array_merge(
                $this->annotations['p'][$className][$propertyName],
                $annotations
            );
        } else {
            $this->annotations['p'][$className][$propertyName] = $annotations;
        }
    }

    public function collectInjection(BetterReflectionProperty $property, $injection)
    {
        $className                                         = $property->getDeclaringClass()
                                                                      ->getName();
        $propertyName                                      = $property->getName();
        $this->annotations['i'][$className][$propertyName] = $injection;
    }

    /**
     * @param AnnotationCollector $instance
     */
    public static function setInstance(self $instance)
    {
        self::$instance = $instance;
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * @return array
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    public function rebuild()
    {
        foreach ($this->annotations['c'] as $class => $annotations) {
            if (!isset(self::$container[$class]['c'])) {
                self::$container[$class]['c'] = $annotations;
            } else {
                self::$container[$class]['c'] = array_merge(self::$container[$class]['c'], $annotations);
            }
        }

        foreach ($this->annotations['m'] as $class => $annotations) {
            if (!isset(self::$container[$class]['m'])) {
                self::$container[$class]['m'] = $annotations;
            } else {
                self::$container[$class]['m'] = array_merge(self::$container[$class]['m'], $annotations);
            }
        }

        foreach ($this->annotations['p'] as $class => $annotations) {
            if (!isset(self::$container[$class]['p'])) {
                self::$container[$class]['p'] = $annotations;
            } else {
                self::$container[$class]['p'] = array_merge(self::$container[$class]['p'], $annotations);
            }
        }
//        var_dump(self::$container);
    }

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
}