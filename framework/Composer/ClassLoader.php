<?php

namespace Laras\Composer;

use App\Http\Controllers\HttpController;
use Composer\Autoload\ClassLoader as ComposerClassLoader;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Exception;
use Laras\Annotation\AnnotationCollector;
use Laras\Aspect\Annotation\Aspect;
use Laras\Aspect\Aop\AstVisitorRegistry;
use Laras\Aspect\Aop\ProxyCallVisitor;
use Laras\Aspect\Aop\ProxyManager;
use Laras\Aspect\AspectCollector;
use Laras\Aspect\AspectLoader;
use ReflectionException;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\TypesFinder\FindPropertyType;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class ClassLoader
 * @package Laras\Composer
 */
class ClassLoader
{
    /**
     * @var ComposerClassLoader
     */
    protected $composerClassLoader;

    /**
     * @var array
     */
    protected $proxies;

    /**
     * @var Finder $finder
     */
    protected $finder;

    /**
     * ClassLoader constructor.
     * @param ComposerClassLoader $classLoader
     * @param string|null $customerProxyFileDir
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function __construct(ComposerClassLoader $classLoader, string $customerProxyFileDir = null)
    {
        $this->setComposerClassLoader($classLoader);

        $this->finder = new Finder();
        $proxyFileDir = realpath(__DIR__ . '/../Proxy') . DIRECTORY_SEPARATOR;

        $this->addProxies($proxyFileDir);

        if (is_null($customerProxyFileDir)) {
            $customerProxyFileDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'Proxy' . DIRECTORY_SEPARATOR;
        }

        if (!is_dir($customerProxyFileDir)) {
            mkdir($customerProxyFileDir, 0777, true);
        }

        $runtimeProxyDir = ROOT_PATH . '/runtime/container/proxy/';

        if (!is_dir($runtimeProxyDir)) {
            mkdir($runtimeProxyDir, 0777, true);
        }

        // overwrite
        $this->addProxies($customerProxyFileDir);

        self::loadAnnotations();
        self::loadAspects();
        $proxyManager = new ProxyManager(
            $this->getComposerClassLoader()
                 ->getClassMap(), $runtimeProxyDir
        );
        $proxies      = $proxyManager->getProxies();

        foreach ($proxies as $class => $path) {
            $this->proxies[$class] = $path;
        }
    }

    /**
     * @param string $dir
     */
    protected function addProxies(string $dir): void
    {
        $files = $this->finder->files()
                              ->name('*.php')
                              ->in($dir);
        foreach ($files as $splFileInfo) {
            /**@var SplFileInfo $splFileInfo */
            $file = $splFileInfo->getPathname();

            $fd   = fopen($file, 'r');
            $line = '';
            $find = false;
            while (!feof($fd)) {
                $line = fgets($fd);
                if (false !== strpos($line, 'namespace')) {
                    $find = true;
                    break;
                }
            }
            if ($find) {
                $namespace                                                            = trim(
                    str_replace(['namespace', ';'], ['', ''], $line)
                );
                $this->proxies[$namespace . '\\' . $splFileInfo->getBasename('.php')] = $file;
            }
        };
    }

    /**
     * @param string|null $customerProxyFileDir
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public static function init(?string $customerProxyFileDir = null): void
    {
        if (!$customerProxyFileDir) {
            $customerProxyFileDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'Proxy' . DIRECTORY_SEPARATOR;
        }

        $loaders = spl_autoload_functions();

        // Proxy the composer class loader
        foreach ($loaders as &$loader) {
            $unregisterLoader = $loader;
            if (is_array($loader) && $loader[0] instanceof ComposerClassLoader) {
                /** @var ComposerClassLoader $composerClassLoader */
                $composerClassLoader = $loader[0];
                $classLoader         = new static($composerClassLoader, $customerProxyFileDir);
                AnnotationRegistry::registerLoader(
                    function ($class) use ($classLoader) {
                        return (bool)$classLoader->locateFile($class);
                    }
                );
                $loader[0] = $classLoader;
            }
            spl_autoload_unregister($unregisterLoader);
        }

        unset($loader);

        // Re-register the loaders
        foreach ($loaders as $loader) {
            spl_autoload_register($loader);
        }
    }

    public function setComposerClassLoader(ComposerClassLoader $classLoader): self
    {
        $this->composerClassLoader = $classLoader;

        return $this;
    }

    public function loadClass(string $class): void
    {
        $path = $this->locateFile($class);

        if ($path) {
            include "{$path}";
        }
    }

    protected function locateFile(string $className): ?string
    {
        if (isset($this->proxies[$className]) && file_exists($this->proxies[$className])) {
            $file = $this->proxies[$className];
        } else {
            $file = $this->getComposerClassLoader()
                         ->findFile($className);
        }

        return is_string($file) ? $file : null;
    }

    public function getComposerClassLoader(): ComposerClassLoader
    {
        return $this->composerClassLoader;
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public static function loadAnnotations()
    {
        $configAnnotation = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'annotation.php';

        $annotationFiles = [];
        if (is_file($configAnnotation)) {
            $array = require "{$configAnnotation}";
            if (isset($array['scan'])) {
                $annotationFiles = $array['scan'];
            }
        }

        $files = Finder::create()
                       ->files()
                       ->name('*.php')
                       ->in($annotationFiles);

        /**
         * @var AnnotationReader $annotationReader
         */
        $annotationReader = new AnnotationReader();

        $annotationCollector = new AnnotationCollector();

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

            $reflectionClass = new \ReflectionClass($class);

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

            $betterPropertyTypeFinder   = new FindPropertyType();
            $betterReflectClass         = (new BetterReflection())->classReflector()
                                                                  ->reflect($class);
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

        $annotationCollector->rebuild();
        AnnotationCollector::setInstance($annotationCollector);
    }

    public static function loadAspects()
    {
        if (!AstVisitorRegistry::exists(ProxyCallVisitor::class)) {
            AstVisitorRegistry::insert(ProxyCallVisitor::class, PHP_INT_MAX / 2);
        }

        $aspects = array_unique(array_merge(static::getConfigAspects(), static::loadAnnotationAspects()));

        foreach ($aspects as $aspect) {
            [$instanceClasses, $instanceAnnotations, $instancePriority] = AspectLoader::load($aspect);

            $classes = $instanceClasses ?: [];
            // Annotations
            $annotations = $instanceAnnotations ?: [];

            AspectCollector::setAround($aspect, $classes, $annotations);
        }
    }

    /**
     * @return array|mixed
     */
    protected static function getConfigAspects()
    {
        $configAspect = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'aspect.php';
        $aspects      = [];
        if (is_file($configAspect)) {
            $aspects = require "{$configAspect}";
        }

        return $aspects;
    }

    /**
     * @return array
     */
    protected static function loadAnnotationAspects()
    {
        $aspects     = [];
        $annotations = AnnotationCollector::getContainer();

        foreach ($annotations as $class => $annotationItems) {
            if (isset($annotationItems['c']) && count($annotationItems['c']) > 0) {
                foreach ($annotationItems['c'] as $item) {
                    if ($item instanceof Aspect) {
                        $aspects[] = $class;
                    }
                }
            }
        }

        return $aspects;
    }
}