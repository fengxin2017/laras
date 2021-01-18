<?php

namespace Laras\Composer;

use Composer\Autoload\ClassLoader as ComposerClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;
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

        // overwrite
        $this->addProxies($customerProxyFileDir);
    }

    /**
     * @param string $dir
     */
    protected function addProxies(string $dir): void
    {
        $files = $this->finder->files()->name('*.php')->in($dir);
        foreach ($files as $splFileInfo) {
            /**@var SplFileInfo $splFileInfo */
            $file = $splFileInfo->getPathname();

            $fd = fopen($file, 'r');
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
                $namespace = trim(str_replace(['namespace', ';'], ['', ''], $line));
                $this->proxies[$namespace . '\\' . $splFileInfo->getBasename('.php')] = $file;
            }
        };
    }

    /**
     * @param string|null $customerProxyFileDir
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
                $classLoader = new static($composerClassLoader, $customerProxyFileDir);

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
            $file = $this->getComposerClassLoader()->findFile($className);
        }

        return is_string($file) ? $file : null;
    }

    public function getComposerClassLoader(): ComposerClassLoader
    {
        return $this->composerClassLoader;
    }
}