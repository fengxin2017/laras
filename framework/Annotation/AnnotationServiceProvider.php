<?php

namespace Laras\Annotation;

use App\Http\Controllers\HttpController;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Exception;
use Illuminate\Support\ServiceProvider;
use Laras\Foundation\Application;
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
        $this->app->instance(AnnotationCollector::class, AnnotationCollector::getInstance());
    }

    /**
     * @throws Exception
     */
    public function boot()
    {

    }
}