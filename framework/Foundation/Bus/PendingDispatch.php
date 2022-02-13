<?php

namespace Laras\Foundation\Bus;

use Laras\AsyncQueue\Producer;
use Laras\Foundation\Application;

class PendingDispatch
{
    /**
     * The job.
     *
     * @var mixed
     */
    protected $job;


    /**
     * Create a new pending job dispatch.
     *
     * @param mixed $job
     * @return void
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

    /**
     * Set the desired delay for the job.
     *
     * @param $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->job->delay($delay);

        return $this;
    }

    /**
     * @param string $queue
     * @return $this
     */
    public function onQueue(string $queue)
    {
        $this->job->onQueue($queue);

        return $this;
    }

    /**
     * Dynamically proxy methods to the underlying job.
     *
     * @param string $method
     * @param array $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        $this->job->{$method}(...$parameters);

        return $this;
    }

    public function __destruct()
    {
        Application::getInstance()->get(Producer::class)->produce($this->job->getQueue(), $this->job, $this->job->getDelay());
    }
}
