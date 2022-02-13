<?php


namespace Laras\Foundation\Bus;


abstract class Job
{
    use Dispatchable;

    protected $delay = 0;

    protected $queue = 'default';

    abstract public function handle();

    /**
     * @param $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->delay = $delay;
        return $this;
    }

    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * @param string $queue
     * @return $this
     */
    public function onQueue(string $queue)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }
}