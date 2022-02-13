<?php


namespace Laras\Crontab;


use App\Crontab\Kernel;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Laras\Annotation\AnnotationCollector;
use Laras\AsyncQueue\Producer;
use Laras\Process\Process;
use Laras\Support\Annotation\Crontab;
use Swoole\Coroutine;

/**
 * Class CrontabProcess
 * @package Laras\Process
 */
class CrontabProcess extends Process
{
    /**
     * @throws BindingResolutionException
     * @throws Exception;
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

        $producer = $this->app->get(Producer::class);

        while (true) {
            // 每分钟第0秒开始解析
            if (date('s', time()) == 0) {
                $crontabs = $scheduler->schedule();
                while (!$crontabs->isEmpty()) {
                    $crontab = $crontabs->dequeue();
                    $producer->produce($crontab->getQueue(), $crontab, Carbon::createFromTimestamp($crontab->getExecuteTime()->timestamp));
                }
            }

            Coroutine::sleep(1);
        }
    }
}