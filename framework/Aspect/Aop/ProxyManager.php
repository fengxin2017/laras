<?php

namespace Laras\Aspect\Aop;

use App\Http\Controllers\HttpController;
use Laras\Annotation\AnnotationCollector;
use Laras\Aspect\AspectCollector;
use Illuminate\Filesystem\Filesystem;
use const http\Client\Curl\Versions\ARES;

class ProxyManager
{
    /**
     * The map to collect the classes whith paths.
     *
     * @var array
     */
    protected $classMap = [];

    /**
     * The classes which be rewrited by proxy.
     *
     * @var array
     */
    protected $proxies = [];

    /**
     * The directory which the proxy file places in.
     *
     * @var string
     */
    protected $proxyDir;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(
        array $classMap = [],
        string $proxyDir = ''
    ) {
        $this->classMap = $classMap;
        $this->proxyDir = $proxyDir;
        $this->filesystem = new Filesystem();
        $this->proxies = $this->generateProxyFiles($this->initProxiesByReflectionClassMap(
            $this->classMap
        ));
    }

    public function getProxies(): array
    {
        return $this->proxies;
    }

    public function getProxyDir(): string
    {
        return $this->proxyDir;
    }

    protected function generateProxyFiles(array $proxies = []): array
    {
        $proxyFiles = [];
        if (! $proxies) {
            return $proxyFiles;
        }
        if (! file_exists($this->getProxyDir())) {
            mkdir($this->getProxyDir(), 0755, true);
        }
        // WARNING: Ast class SHOULD NOT use static instance, because it will read  the code from file, then would be caused coroutine switch.
        $ast = new Ast();
        foreach ($proxies as $className => $aspects) {
            $proxyFiles[$className] = $this->putProxyFile($ast, $className);
        }
        return $proxyFiles;
    }

    protected function putProxyFile(Ast $ast, $className)
    {
        $proxyFilePath = $this->getProxyFilePath($className);
        $modified = true;
        if (file_exists($proxyFilePath)) {
            $modified = $this->isModified($className, $proxyFilePath);
        }

        if ($modified) {
            $code = $ast->proxy($className);
            file_put_contents($proxyFilePath, $code);
        }

        return $proxyFilePath;
    }

    protected function isModified(string $className, string $proxyFilePath = null): bool
    {
        $proxyFilePath = $proxyFilePath ?? $this->getProxyFilePath($className);
        $time = $this->filesystem->lastModified($proxyFilePath);
        $origin = $this->classMap[$className];
        if ($time >= $this->filesystem->lastModified($origin)) {
            return false;
        }

        return true;
    }

    protected function getProxyFilePath($className)
    {
        return $this->getProxyDir() . str_replace('\\', '_', $className) . '.proxy.php';
    }

    protected function isMatch(string $rule, string $target): bool
    {
        if (strpos($rule, '::') !== false) {
            [$rule,] = explode('::', $rule);
        }
        if (strpos($rule, '*') === false && $rule === $target) {
            return true;
        }
        $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $rule);
        $pattern = "/^{$preg}$/";

        if (preg_match($pattern, $target)) {
            return true;
        }

        return false;
    }

    protected function initProxiesByReflectionClassMap(array $reflectionClassMap = []): array
    {
        // According to the data of AspectCollector to parse all the classes that need proxy.
        $proxies = [];
        if (! $reflectionClassMap) {
            return $proxies;
        }

        $classesAspects = AspectCollector::get('classes', []);

        foreach ($classesAspects as $aspect => $rules) {
            foreach ($rules as $rule) {
                foreach ($reflectionClassMap as $class => $path) {
                    if (! $this->isMatch($rule, $class)) {
                        continue;
                    }
                    $proxies[$class][] = $aspect;
                }
            }
        }
        foreach ($reflectionClassMap as $className => $path) {
            // Aggregate the class annotations
            $classAnnotations = $this->retrieveAnnotations($className . '.c');
            // Aggregate all methods annotations
            $methodAnnotations = $this->retrieveAnnotations($className . '.m');
            // Aggregate all properties annotations
            $propertyAnnotations = $this->retrieveAnnotations($className . '.p');
            $annotations = array_unique(array_merge($classAnnotations, $methodAnnotations,$propertyAnnotations));

            if ($annotations) {
                $annotationsAspects = AspectCollector::get('annotations', []);
                foreach ($annotationsAspects as $aspect => $rules) {
                    foreach ($rules as $rule) {
                        foreach ($annotations as $annotation) {
                            if ($this->isMatch($rule, $annotation)) {
                                $proxies[$className][] = $aspect;
                            }
                        }
                    }
                }
            }
        }

        return $proxies;
    }

    protected function retrieveAnnotations(string $annotationCollectorKey): array
    {
        $defined = [];
        $annotations = AnnotationCollector::get($annotationCollectorKey, []);

        foreach ($annotations as $name => $annotation) {
            if (is_object($annotation)) {
                $defined[] = get_class($annotation);
            } else {
                $items = [];
                $annotations = array_values($annotation);
                foreach ($annotations as $anno){
                    $items[] = get_class($anno);
                }
                $defined = array_merge($defined, $items);
            }
        }

        return $defined;
    }
}
