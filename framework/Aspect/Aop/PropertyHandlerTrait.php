<?php

namespace Laras\Aspect\Aop;

use Laras\Annotation\AnnotationCollector;
use ReflectionClass;
use ReflectionException;

trait PropertyHandlerTrait
{
    /**
     * @param string $className
     * @throws ReflectionException
     */
    protected function __handlePropertyHandler(string $className)
    {
        $reflectionClass = new ReflectionClass($className);

        $reflectProperties = (new ReflectionClass($className))->getProperties();
        $properties = [];
        foreach ($reflectProperties as $reflectionProperty) {
            $properties[] = $reflectionProperty->getName();
        }

        // Inject the properties of current class
        $handled = $this->__handle($className, $className, $properties);

        // Inject the properties of traits.
        // Because the property annotations of trait couldn't be collected by class.
        $handled = $this->__handleTrait($reflectionClass, $handled, $className);

        // Inject the properties of parent class.
        // It can be used to deal with parent classes whose subclasses have constructor function, but don't execute `parent::__construct()`.
        // For example:
        // class SubClass extend ParentClass
        // {
        //     public function __construct() {
        //     }
        // }
        $parentReflectionClass = $reflectionClass;
        while ($parentReflectionClass = $parentReflectionClass->getParentClass()) {
            $properties = (new ReflectionClass($parentReflectionClass->getName()))->getProperties();
            $parentClassProperties = [];
            foreach ($properties as $property) {
                $parentClassProperties[] = $property->getName();
            }
            $parentClassProperties = array_filter($parentClassProperties, static function ($property) use ($reflectionClass) {
                return $reflectionClass->hasProperty($property);
            });
            $parentClassProperties = array_diff($parentClassProperties, $handled);
            $handled = array_merge(
                $handled,
                $this->__handle($className, $parentReflectionClass->getName(), $parentClassProperties)
            );
        }
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @param array $handled
     * @param string $className
     * @return array
     * @throws ReflectionException
     */
    protected function __handleTrait(\ReflectionClass $reflectionClass, array $handled, string $className): array
    {
        foreach ($reflectionClass->getTraits() ?? [] as $reflectionTrait) {
            if (in_array($reflectionTrait->getName(), [ProxyTrait::class, PropertyHandlerTrait::class])) {
                continue;
            }

            $properties = (new ReflectionClass($reflectionTrait->getName()))->getProperties();
            $traitProperties = [];
            foreach ($properties as $property) {
                $traitProperties[] = $property->getName();
            }

            $traitProperties = array_diff($traitProperties, $handled);
            if (! $traitProperties) {
                continue;
            }
            $handled = array_merge(
                $handled,
                $this->__handle($className, $reflectionTrait->getName(), $traitProperties)
            );
            $handled = $this->__handleTrait($reflectionTrait, $handled, $className);
        }
        return $handled;
    }

    protected function __handle(string $currentClassName, string $targetClassName, array $properties): array
    {
        $handled = [];
        foreach ($properties as $propertyName) {
            $propertyMetadata = AnnotationCollector::get($targetClassName . '.p.' . $propertyName);
            if (! $propertyMetadata) {
                continue;
            }
            foreach ($propertyMetadata as $annotation) {
                $annotationName = get_class($annotation);
                if ($callbacks = PropertyHandlerManager::get($annotationName)) {
                    foreach ($callbacks as $callback) {
                        call($callback, [$this, $currentClassName, $targetClassName, $propertyName, $annotation]);
                    }
                    $handled[] = $propertyName;
                }
            }
        }

        return $handled;
    }
}
