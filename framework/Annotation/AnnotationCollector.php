<?php


namespace Laras\Annotation;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;

class AnnotationCollector
{
    protected $annotations = [];

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
        $className = $method->getDeclaringClass()->getName();
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
        $className = $property->getDeclaringClass()->getName();
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
        $className = $property->getDeclaringClass()->getName();
        $propertyName = $property->getName();
        $this->annotations['i'][$className][$propertyName] = $injection;
    }

    /**
     * @return array
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }
}