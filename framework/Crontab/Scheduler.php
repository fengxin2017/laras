<?php

namespace Laras\Crontab;

use SplQueue;

class Scheduler
{
    /**
     * @var CrontabManager
     */
    protected $crontabManager;

    /**
     * @var SplQueue
     */
    protected $schedules;

    public function __construct(CrontabManager $crontabManager)
    {
        $this->schedules = new SplQueue();
        $this->crontabManager = $crontabManager;
    }

    public function schedule(): SplQueue
    {
        foreach ($this->getSchedules() ?? [] as $schedule) {
            $this->schedules->enqueue($schedule);
        }
        return $this->schedules;
    }

    protected function getSchedules(): array
    {
        return $this->crontabManager->parse();
    }
}
