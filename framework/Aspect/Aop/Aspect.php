<?php

namespace Laras\Aspect\Aop;

use Laras\Annotation\AnnotationCollector;
use Laras\Aspect\AspectCollector;

class Aspect
{
    /**
     * @param string $class
     * @return RewriteCollection
     */
    public static function parse(string $class): RewriteCollection
    {
        $rewriteCollection = new RewriteCollection($class);
        $container = AspectCollector::list();

        foreach ($container as $type => $collection) {
            if ($type === 'classes') {
                static::parseClasses($collection, $class, $rewriteCollection);
            } elseif ($type === 'annotations') {
                static::parseAnnotations($collection, $class, $rewriteCollection);
            }
        }
        return $rewriteCollection;
    }

    /**
     * @param string $target
     * @param string $rule
     * @return array
     */
    public static function isMatchClassRule(string $target, string $rule): array
    {
        /*
         * e.g. Foo/Bar
         * e.g. Foo/B*
         * e.g. F*o/Bar
         * e.g. Foo/Bar::method
         * e.g. Foo/Bar::met*
         */
        $ruleMethod = null;
        $ruleClass = $rule;
        $method = null;
        $class = $target;

        if (strpos($rule, '::') !== false) {
            [$ruleClass, $ruleMethod] = explode('::', $rule);
        }
        if (strpos($target, '::') !== false) {
            [$class, $method] = explode('::', $target);
        }

        if ($method == null) {
            if (strpos($ruleClass, '*') === false) {
                /*
                 * Match [rule] Foo/Bar::ruleMethod [target] Foo/Bar [return] true,ruleMethod
                 * Match [rule] Foo/Bar [target] Foo/Bar [return] true,null
                 * Match [rule] FooBar::rule*Method [target] Foo/Bar [return] true,rule*Method
                 */
                if ($ruleClass === $class) {
                    return [true, $ruleMethod];
                }

                return [false, null];
            }

            /**
             * Match [rule] Foo*Bar::ruleMethod [target] Foo/Bar [return] true,ruleMethod
             * Match [rule] Foo*Bar [target] Foo/Bar [return] true,null.
             */
            $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $ruleClass);
            $pattern = "#^{$preg}$#";

            if (preg_match($pattern, $class)) {
                return [true, $ruleMethod];
            }

            return [false, null];
        }

        if (strpos($rule, '*') === false) {
            /*
             * Match [rule] Foo/Bar::ruleMethod [target] Foo/Bar::ruleMethod [return] true,ruleMethod
             * Match [rule] Foo/Bar [target] Foo/Bar::ruleMethod [return] false,null
             */
            if ($ruleClass === $class && ($ruleMethod === null || $ruleMethod === $method)) {
                return [true, $method];
            }

            return [false, null];
        }

        /*
         * Match [rule] Foo*Bar::ruleMethod [target] Foo/Bar::ruleMethod [return] true,ruleMethod
         * Match [rule] FooBar::rule*Method [target] Foo/Bar::ruleMethod [return] true,rule*Method
         */
        if ($ruleMethod) {
            $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $rule);
            $pattern = "#^{$preg}$#";
            if (preg_match($pattern, $target)) {
                return [true, $method];
            }
        } else {
            /**
             * Match [rule] Foo*Bar [target] Foo/Bar::ruleMethod [return] true,null.
             */
            $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $rule);
            $pattern = "#^{$preg}$#";
            if (preg_match($pattern, $class)) {
                return [true, $method];
            }
        }

        return [false, null];
    }

    public static function isMatch(string $class, string $method, string $rule): bool
    {
        [$isMatch,] = self::isMatchClassRule($class . '::' . $method, $rule);

        return $isMatch;
    }

    private static function parseAnnotations(array $collection, string $class, RewriteCollection $rewriteCollection)
    {
        // Get the annotations of class and method.
        $annotations = AnnotationCollector::get($class);
        $classMapping = $annotations['c'] ?? [];
        $classMap = [];
        foreach ($classMapping as $obj) {
            $classMap[get_class($obj)] = $obj;
        }

        $methodMapping = value(
            function () use ($annotations) {
                $mapping = [];
                $methodAnnotations = $annotations['m'] ?? [];
                foreach ($methodAnnotations as $method => $targetAnnotations) {
                    $keys = [];
                    foreach ($targetAnnotations as $targetAnnotation) {
                        $keys[] = get_class($targetAnnotation);
                    }
                    
                    foreach ($keys as $key) {
                        $mapping[$key][] = $method;
                    }
                }
                return $mapping;
            }
        );


        $aspects = array_keys($collection);
        foreach ($aspects ?? [] as $aspect) {
            $rules = AspectCollector::getRule($aspect);
            foreach ($rules['annotations'] ?? [] as $rule) {
                // If exist class level annotation, then all methods should rewrite, so return an empty array directly.
                if (isset($classMap[$rule])) {
                    return $rewriteCollection->setLevel(RewriteCollection::CLASS_LEVEL);
                }
                if (isset($methodMapping[$rule])) {
                    $rewriteCollection->add($methodMapping[$rule]);
                }
            }
        }

        return $rewriteCollection;
    }

    private static function parseClasses(array $collection, string $class, RewriteCollection $rewriteCollection)
    {
        $aspects = array_keys($collection);
        foreach ($aspects ?? [] as $aspect) {
            $rules = AspectCollector::getRule($aspect);
            foreach ($rules['classes'] ?? [] as $rule) {
                [$isMatch, $method] = static::isMatchClassRule($class, $rule);
                if ($isMatch) {
                    if ($method === null) {
                        return $rewriteCollection->setLevel(RewriteCollection::CLASS_LEVEL);
                    }
                    $rewriteCollection->add($method);
                }
            }
        }
    }
}
