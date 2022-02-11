<?php


namespace Laras\Process;


use App\Crontab\Kernel;
use Illuminate\Contracts\Container\BindingResolutionException;
use Laras\Annotation\AnnotationCollector;
use Laras\Crontab\CrontabManager;
use Laras\Crontab\Scheduler;
use Laras\Facades\Log;
use Laras\Facades\Redis;
use Laras\Support\Annotation\Crontab;
use Swoole\Coroutine;

class CrontabProcess extends Process
{
    /**
     * @throws BindingResolutionException
     */
    public function process()
    {
        /**@var CrontabManager $crontabManager */
        $crontabManager = $this->app->make(CrontabManager::class);
        /**@var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->schedule();
        $jobs = $kernel->getJobs();
        foreach ($jobs as $job) {
            $jobClass = get_class($job);
            if (isset(AnnotationCollector::getContainer()[$jobClass]['c'])) {
                $annotationClasses = AnnotationCollector::getContainer()[$jobClass]['c'];
                foreach ($annotationClasses as $annotation) {
                    if ($annotation instanceof Crontab) {
                        if ($annotation->rule) {
                            $job->setRule($annotation->rule);
                        }
                    }
                }
            }
            $job->setName($jobClass);
            $crontabManager->register($job);
        }

        $scheduler = new Scheduler($crontabManager);

        while (true) {
            if (date('s', time()) == 0) {
                $crontabs = $scheduler->schedule();
                while (!$crontabs->isEmpty()) {
                    $crontab = $crontabs->dequeue();
                    Redis::zAdd('crontabJob', $crontab->getExecuteTime()->timestamp, serialize($crontab));
                }
            }

            $now = time();
            $jobs = Redis::zRangeByScore('crontabJob', '-inf', $now);
            Redis::zRemRangeByScore('crontabJob', '-inf', $now);
            foreach ($jobs as $job) {
                try {
                    $job = unserialize($job);
                    $job->execute();
                } catch (\Throwable $throwable) {
                    Log::error($throwable->getMessage());
                    Redis::lpush('crontabFailedJob', serialize($job));
                }
            }

            Coroutine::sleep(1);
        }
    }
}