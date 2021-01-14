<?php

namespace MoneyMaker\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Exception;
use Illuminate\Support\ServiceProvider;
use MoneyMaker\Foundation\Application;
use ReflectionClass;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\TypesFinder\FindPropertyType;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class AnnotationServiceProvider extends ServiceProvider
{

    /**
     * @var Application $app
     */
    protected $app;

    public function register()
    {
        $this->app->instance(Reader::class, new AnnotationReader());
        $this->app->instance(AnnotationCollector::class, new AnnotationCollector());
    }

    /**
     * @throws Exception
     */
    public function boot()
    {
        $files = Finder::create()->files()->name('*.php')->in($this->app['config']['annotation']['scan']);

        /**
         * @var AnnotationReader $annotationReader
         */
        $annotationReader = $this->app->make(Reader::class);
        /**
         * @var AnnotationCollector $annotationCollector
         */
        $annotationCollector = $this->app->make(AnnotationCollector::class);

        foreach ($files as $file) {
            /**@var SplFileInfo $file */
            $fd = fopen($file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename(), 'r');
            while (!feof($fd)) {
                $line = fgets($fd);
                if (false !== strpos($line, 'namespace')) {
                    break;
                }
            }

            if (!isset($line)) {
                throw new Exception(sprintf('Namespce missing in' . $file->getFilename()));
            }
            $namespace = trim(str_replace(['namespace', ';'], ['', ''], $line));
            if (!$namespace) {
                continue;
            }

            $class = $namespace . '\\' . $file->getBasename('.php');

            $reflectionClass = new ReflectionClass($class);

            $annotationCollector->collectClass(
                $reflectionClass,
                $annotationReader->getClassAnnotations($reflectionClass)
            );

            $reflectionMethods = $reflectionClass->getMethods();

            foreach ($reflectionMethods as $method) {
                $annotationCollector->collectMethod(
                    $method,
                    $annotationReader->getMethodAnnotations($method)
                );
            }

            $reflectionProperties = $reflectionClass->getProperties();

            foreach ($reflectionProperties as $property) {
                $annotationCollector->collectProperty(
                    $property,
                    $annotationReader->getPropertyAnnotations($property)
                );
            }

            $betterPropertyTypeFinder = new FindPropertyType();
            $betterReflectClass = (new BetterReflection())->classReflector()->reflect($class);
            $betterReflectionProperties = $betterReflectClass->getImmediateProperties();

            foreach ($betterReflectionProperties as $betterReflectionProperty) {
                $inject = ltrim(
                    (string)current(
                        $betterPropertyTypeFinder(
                            $betterReflectionProperties[$betterReflectionProperty->getName()],
                            $betterReflectClass->getDeclaringNamespaceAst()
                        )
                    ),
                    '\\'
                );
                if ($inject) {
                    $annotationCollector->collectInjection($betterReflectionProperty, $inject);
                }
            }
        }
    }
}