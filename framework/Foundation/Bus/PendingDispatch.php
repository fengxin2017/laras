<?php

namespace MoneyMaker\Foundation\Bus;

use Carbon\Carbon;
use Swoole\Timer;

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
        $delay = $this->job->delay;

        if (!$delay) {
            $this->job->handle();
            return;
        }

        if ($delay instanceof Carbon) {
            $delay = $delay->diffInSeconds(Carbon::now()->subSecond());
        }

        if ($delay > 0) {
            Timer::after(
                $delay * 1000,
                function () {
                    $this->job->handle();
                }
            );
        }
    }
}
