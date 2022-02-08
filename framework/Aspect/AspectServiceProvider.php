<?php


namespace Laras\Aspect;


use Composer\Autoload\ClassLoader as ComposerClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Laras\Annotation\AnnotationCollector;
use Laras\Aspect\Annotation\Aspect;
use Laras\Aspect\Aop\AstVisitorRegistry;
use Laras\Aspect\Aop\ProxyCallVisitor;
use Laras\Aspect\Aop\ProxyManager;
use Laras\Composer\ClassLoader;
use ReflectionException;

class AspectServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function register()
    {
//        if (! AstVisitorRegistry::exists(ProxyCallVisitor::class)) {
//            AstVisitorRegistry::insert(ProxyCallVisitor::class, PHP_INT_MAX / 2);
//        }
//
//        $aspects = array_unique(array_merge($this->getConfigAspects(), $this->loadAnnotationAspects()));
//
//        foreach ($aspects as $aspect) {
//            [$instanceClasses, $instanceAnnotations, $instancePriority] = AspectLoader::load($aspect);
//
//            $classes = $instanceClasses ?: [];
//            // Annotations
//            $annotations = $instanceAnnotations ?: [];
//
//            AspectCollector::setAround($aspect, $classes, $annotations);
//        }
//
//        $proxyManager = new ProxyManager(ClassLoader::getClassMap(), ROOT_PATH . '/runtime/container/proxy/');
//        $proxies = $proxyManager->getProxies();
//
//        foreach ($proxies as $class => $path){
//            ClassLoader::setProxy($class, $path);
//        }
    }

    public function boot()
    {

    }

    /**
     * @return mixed
     */
    protected function getConfigAspects()
    {
        return $this->app->get('config')['aspect'];
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    protected function loadAnnotationAspects()
    {
        $aspects     = [];
        $annotations = $this->app->make(AnnotationCollector::class)
                                 ->getAnnotations()['c'];

        foreach ($annotations as $class => $annotationItems) {
            if (count($annotationItems) > 0) {
                foreach ($annotationItems as $item) {
                    if ($item instanceof Aspect) {
                        $aspects[] = $class;
                    }
                }
            }
        }

        return $aspects;
    }
}