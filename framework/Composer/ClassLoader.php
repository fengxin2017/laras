<?php

namespace Laras\Composer;

use Composer\Autoload\ClassLoader as ComposerClassLoader;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Exception;
use Illuminate\Filesystem\Filesystem;
use Laras\Annotation\AnnotationCollector;
use Laras\Aspect\Aop\AstVisitorRegistry;
use Laras\Aspect\Aop\PropertyHandlerVisitor;
use Laras\Aspect\Aop\ProxyCallVisitor;
use Laras\Aspect\Aop\ProxyManager;
use Laras\Aspect\Aop\RegisterInjectPropertyHandler;
use Laras\Aspect\AspectCollector;
use Laras\Aspect\AspectLoader;
use Laras\Support\Annotation\Aspect;
use ReflectionClass;
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
    protected ComposerClassLoader $composerClassLoader;

    /**
     * @var array
     */
    protected array $proxies;

    /**
     * @var Finder $finder
     */
    protected Finder $finder;

    /**
     * @var string $scanCachePath
     */
    protected string $scanCachePath = ROOT_PATH . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'container' . DIRECTORY_SEPARATOR . 'scan.cache';

    /**
     * @var string $serviceClassMapDir
     */
    protected string $serviceClassMapDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'ClassMap' . DIRECTORY_SEPARATOR;

    /**
     * @var string $runtimeProxyDir
     */
    protected string $runtimeProxyDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'container' . DIRECTORY_SEPARATOR . 'proxy' . DIRECTORY_SEPARATOR;

    /**
     * @var string $classMapDir
     */
    protected string $classMapDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'class_map' . DIRECTORY_SEPARATOR;

    /**
     * @var self $instance
     */
    public static self $instance;

    /**
     * ClassLoader constructor.
     * @param ComposerClassLoader $classLoader
     * @param string $classMapDir
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(ComposerClassLoader $classLoader, string $classMapDir = null)
    {
        $this->setComposerClassLoader($classLoader);

        $this->finder = new Finder();

        // serviceClassMap
        $serviceClassMap = realpath($this->serviceClassMapDir) . DIRECTORY_SEPARATOR;

        $this->addProxies($serviceClassMap);

        // overwrite
        if (!is_null($classMapDir)) {
            $this->classMapDir = $classMapDir;
        }

        if (!is_dir($this->classMapDir)) {
            mkdir($this->classMapDir, 0755, true);
        }

        // ApplicationClassMap
        $this->addProxies($this->classMapDir);

        // generate runtime proxy dir
        if (!is_dir($this->runtimeProxyDir)) {
            mkdir($this->runtimeProxyDir, 0755, true);
        }

        if (!AstVisitorRegistry::exists(PropertyHandlerVisitor::class)) {
            AstVisitorRegistry::insert(PropertyHandlerVisitor::class, PHP_INT_MAX / 2);
        }

        // 在子进程中注册访问器的话当热载的时候就无法获取到访问器需要在这里注册
        if (!AstVisitorRegistry::exists(ProxyCallVisitor::class)) {
            AstVisitorRegistry::insert(ProxyCallVisitor::class, PHP_INT_MAX / 2);
        }

        // Register Property Handler.
        RegisterInjectPropertyHandler::register();

        $pid = pcntl_fork();

        if ($pid == -1) {
            throw new Exception('The process fork failed');
        }
        if ($pid) {
            pcntl_wait($status);
            [$data, $proxies] = unserialize(file_get_contents($this->scanCachePath));
            $annotationData = $data['annotationData'];
            AnnotationCollector::setContainer($annotationData);

            $aspectData = $data['aspectData'];
            AspectCollector::deserialize($aspectData);

            foreach ($proxies as $class => $path) {
                $this->proxies[$class] = $path;
            }
        } else {
            $annotationData = $this->loadAnnotations();
            $aspectData = $this->loadAspects();

            $proxyManager = new ProxyManager(
                $this->getComposerClassLoader()
                    ->getClassMap(), $this->runtimeProxyDir
            );
            $proxies = $proxyManager->getProxies();

            $data = [
                'annotationData' => $annotationData,
                'aspectData' => $aspectData,
            ];
            $this->putCache($this->scanCachePath, serialize([$data, $proxies]));
            exit();
        }
    }

    // hot-reload regenerate proxy files
    public function reProxy()
    {
        new ProxyManager(
            $this->getComposerClassLoader()->getClassMap(),
            $this->runtimeProxyDir
        );
    }

    protected function putCache(string $path, $data)
    {
        $filesystem = new Filesystem();
        if (!$filesystem->isDirectory($dir = dirname($path))) {
            $filesystem->makeDirectory($dir, 0755, true);
        }

        $filesystem->put($path, $data);
    }

    /**
     * @param string $dir
     */
    protected function addProxies(string $dir): void
    {
        $files = $this->finder->files()->name('*.php')->in($dir);

        foreach ($files as $splFileInfo) {
            /**@var SplFileInfo $splFileInfo */
            $fileName = $splFileInfo->getPathname();

            $fd = fopen($fileName, 'r');
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
                $namespace = trim(
                    str_replace(['namespace', ';'], ['', ''], $line)
                );
                $this->proxies[$namespace . '\\' . $splFileInfo->getBasename('.php')] = $fileName;
            }
        };
    }

    /**
     * @param string|null $classMapDir
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public static function init(?string $classMapDir = null): void
    {
        $loaders = spl_autoload_functions();

        // Proxy the composer class loader
        foreach ($loaders as &$loader) {
            $unregisterLoader = $loader;
            if (is_array($loader) && $loader[0] instanceof ComposerClassLoader) {
                /** @var ComposerClassLoader $composerClassLoader */
                $composerClassLoader = $loader[0];
                $classLoader = new static($composerClassLoader, $classMapDir);
                static::$instance = $classLoader;
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
     * @return array
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws Exception
     */
    public function loadAnnotations()
    {
        $appConfigFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';

        $annotationFiles = [];

        if (is_file($appConfigFile)) {
            $appConfig = require "{$appConfigFile}";
            if (isset($appConfig['annotation']['scan'])) {
                $annotationFiles = $appConfig['annotation']['scan'];
            }
        }

        $files = $this->finder->files()->name('*.php')->in($annotationFiles);

        $annotationReader = new AnnotationReader();
        AnnotationReader::addGlobalIgnoredName('mixin');

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

            $this->collectAnnotation($annotationReader, $class);
        }

        $scanClasses = $appConfig['annotation']['classes'] ?? [];

        foreach ($scanClasses as $scanClass) {
            $this->collectAnnotation($annotationReader, $scanClass);
        }

        return AnnotationCollector::getContainer();
    }

    /**
     * @param AnnotationReader $annotationReader
     * @param string $class
     * @throws ReflectionException
     */
    protected function collectAnnotation(AnnotationReader $annotationReader, string $class)
    {
        $reflectionClass = new ReflectionClass($class);

        AnnotationCollector::collectClass(
            $reflectionClass,
            $annotationReader->getClassAnnotations($reflectionClass)
        );

        $reflectionMethods = $reflectionClass->getMethods();

        foreach ($reflectionMethods as $method) {
            AnnotationCollector::collectMethod(
                $method,
                $annotationReader->getMethodAnnotations($method)
            );
        }

        $reflectionProperties = $reflectionClass->getProperties();

        foreach ($reflectionProperties as $property) {
            AnnotationCollector::collectProperty(
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
                AnnotationCollector::collectInjection($betterReflectionProperty, $inject);
            }
        }
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    public function loadAspects()
    {
        $aspects = array_unique(array_merge(static::getConfigAspects(), static::loadAnnotationAspects()));

        foreach ($aspects as $aspect) {
            [$instanceClasses, $instanceAnnotations, $instancePriority] = AspectLoader::load($aspect);

            $classes = $instanceClasses ?: [];
            // Annotations
            $annotations = $instanceAnnotations ?: [];

            AspectCollector::setAround($aspect, $classes, $annotations, $instancePriority);
        }

        return AspectCollector::serialize();
    }

    /**
     * @return array|mixed
     */
    protected static function getConfigAspects()
    {
        $configAspect = ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'aspect.php';
        $aspects = [];
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
        $aspects = [];
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