<?php

namespace Laras\Aspect;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class AspectLoader
{
    /**
     * @param string $className
     * @return array
     * @throws ReflectionException
     */
    public static function load(string $className): array
    {
        $reflectionClass  = new ReflectionClass($className);
        $properties       = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
        $instanceClasses  = $instanceAnnotations = [];
        $instancePriority = null;
        foreach ($properties as $property) {
            if ($property->getName() === 'classes') {
                $instanceClasses = self::getPropertyDefaultValue($property);
            } elseif ($property->getName() === 'annotations') {
                $instanceAnnotations = self::getPropertyDefaultValue($property);
            } elseif ($property->getName() === 'priority') {
                $instancePriority = self::getPropertyDefaultValue($property);
            }
        }

        return [$instanceClasses, $instanceAnnotations, $instancePriority];
    }

    public static function getPropertyDefaultValue(ReflectionProperty $property)
    {
        return method_exists($property, 'getDefaultValue')
            ? $property->getDefaultValue()
            : $property->getDeclaringClass()
                       ->getDefaultProperties()[$property->getName()] ?? null;
    }
}
