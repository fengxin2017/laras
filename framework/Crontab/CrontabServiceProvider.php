<?php

namespace MoneyMaker\Crontab;

use App\Annotations\Crontab;
use App\Crontab\Kernel;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use MoneyMaker\Annotation\AnnotationCollector;
use MoneyMaker\Contracts\Foundation\Application;
use MoneyMaker\Facades\Log;
use MoneyMaker\Facades\Redis;
use Swoole\Coroutine;
use Swoole\Timer;
use Throwable;

class CrontabServiceProvider extends ServiceProvider
{
    /**
     * @var Application $app
     */
    protected $app;

    public function register()
    {
        $this->app->singleton(CrontabManager::class, function () {
            return new CrontabManager(new Parser());
        });

        $this->app->singleton(Kernel::class, function () {
            return tap(new Kernel(), function (Kernel $kernel) {
                $kernel->schedule();
            });
        });
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot()
    {
        $annotationClasses = $this->app->make(AnnotationCollector::class)->getAnnotations()['c'];

        if ($this->app->getWorkerId() == 0) {
            $crontabManager = $this->app->make(CrontabManager::class);
            /**@var Kernel $kernel */
            $kernel = $this->app->make(Kernel::class);
            $jobs = $kernel->getJobs();

            foreach ($jobs as $job) {
                $jobClass = get_class($job);
                if (isset($annotationClasses[$jobClass])) {
                    foreach ($annotationClasses[$jobClass] as $annotation) {
                        if ($annotation instanceof Crontab) {
                            if ($annotation->name) {
                                $job->setName($annotation->name);
                            }
                            if ($annotation->rule) {
                                $job->setRule($annotation->rule);
                            }
                        }
                    }
                }
                $crontabManager->register($job);
            }

            $scheduler = new Scheduler($crontabManager);

            Timer::after((60 - date('s', time())) * 1000, function () use ($scheduler) {
                $crontabs = $scheduler->schedule();
                while (!$crontabs->isEmpty()) {
                    $crontab = $crontabs->dequeue();
                    Redis::zAdd('crontabJob', $crontab->getExecuteTime()->timestamp, serialize($crontab));
                }
                Timer::tick(60 * 1000, function () use ($scheduler) {
                    $crontabs = $scheduler->schedule();
                    while (!$crontabs->isEmpty()) {
                        $crontab = $crontabs->dequeue();
                        Redis::zAdd('crontabJob', $crontab->getExecuteTime()->timestamp, serialize($crontab));
                    }
                });
            });

            Timer::tick(1000, function (int $timerId) {
                $now = time();
                $jobs = Redis::zRangeByScore('crontabJob', '-inf', $now);
                Redis::zRemRangeByScore('crontabJob', '-inf', $now);
                foreach ($jobs as $job) {
                    Coroutine::create(function () use ($job) {
                        try {
                            $job = unserialize($job);
                            $job->execute();
                        } catch (Throwable $throwable) {
                            Log::error($throwable->getMessage());
                            Redis::lpush('crontabFailedJob', serialize($job));
                        }
                    });
                }
            });
        }
    }
}