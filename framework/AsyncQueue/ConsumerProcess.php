<?php


namespace Laras\AsyncQueue;


use Exception;
use Laras\Facades\Config;
use Laras\Facades\Log;
use Laras\Process\Process;
use Laras\Redis\RedisManager;
use Swoole\Coroutine;
use Throwable;

/**
 * Class ConsumerProcess
 * @package Laras\AsyncQueue
 */
class ConsumerProcess extends Process
{
    /**
     * @throws Exception
     */
    public function process()
    {
        $queues = array_keys(Config::get('queue'));

        $redis = $this->app->coMake(RedisManager::class);
        while (true) {
            $now = time();
            foreach ($queues as $queue) {
                $queueConcurrentLimit = Config::get('queue.' . $queue . '.concurrent.limit');
                $queue = 'laras:queue:async:' . $queue;
                $jobs = $redis->zRangeByScore($queue, '-inf', $now);
                $redis->zRemRangeByScore($queue, '-inf', $now);
                while (count($jobs) > 0) {
                    $readyJobs = [];
                    for ($i = 0; $i < $queueConcurrentLimit; $i++) {
                        $job = array_shift($jobs);
                        if ($job) {
                            $readyJobs[] = $job;
                        }
                    }
                    $wg = new Coroutine\WaitGroup();

                    foreach ($readyJobs as $job) {
                        $wg->add();
                        try {
                            $job = unserialize($job);
                            Coroutine::create(function () use ($job, $wg) {
                                $job->handle();
                                $wg->done();
                            });
                        } catch (Throwable $throwable) {
                            Log::error($throwable->getMessage());
                            $redis->lpush($queue . 'FailedJob', serialize($job));
                        }
                    }

                    $wg->wait();
                }
            }
        }
    }
}