<?php

namespace Laras\Aspect\Aop;

use Laras\Foundation\Application;
use Laras\Support\Annotation\Inject;
use ReflectionClass;
use Throwable;

class RegisterInjectPropertyHandler
{
    /**
     * @var bool
     */
    public static $registered = false;

    /**
     *
     */
    public static function register()
    {
        if (static::$registered) {
            return;
        }
        PropertyHandlerManager::register(Inject::class, function ($object, $currentClassName, $targetClassName, $property, $annotation) {
            if ($annotation instanceof Inject) {
                try {
                    $reflectionProperty = (new ReflectionClass($currentClassName))->getProperty($property);
                    $reflectionProperty->setAccessible(true);
                    $container = Application::getInstance();
                    $reflectionProperty->setValue($object, $container->coMake($annotation->inject['value']));
                } catch (Throwable $throwable) {
                    if ($annotation->required) {
                        throw $throwable;
                    }
                }
            }
        });

        static::$registered = true;
    }
}
