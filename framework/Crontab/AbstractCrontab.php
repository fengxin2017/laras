<?php

namespace Laras\Crontab;

use Carbon\Carbon;
use Exception;

abstract class AbstractCrontab
{
    /**
     * @var null|string
     */
    protected $name = __CLASS__;

    /**
     * @var string
     */
    protected $type = 'callback';

    /**
     * @var null|string
     */
    protected $rule = '* * * * * *';

    /**
     * @var bool
     */
    protected $singleton = false;

    /**
     * @var string
     */
    protected $mutexPool = 'default';

    /**
     * @var int
     */
    protected $mutexExpires = 3600;

    /**
     * @var bool
     */
    protected $onOneServer = false;

    /**
     * @var mixed
     */
    protected $callback;

    /**
     * @var null|string
     */
    protected $memo;

    /**
     * @var null|Carbon
     */
    protected $executeTime;

    /**
     * @var string $unique
     */
    protected $unique;

    public function __construct()
    {
        $this->unique = sha1(__CLASS__ . uniqid() . time());
    }

    abstract public function execute();

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): AbstractCrontab
    {
        $this->name = $name;
        return $this;
    }

    public function getRule(): ?string
    {
        return $this->rule;
    }

    public function setRule(?string $rule): AbstractCrontab
    {
        $this->rule = $rule;
        return $this;
    }

    public function isSingleton(): bool
    {
        return $this->singleton;
    }

    public function setSingleton(bool $singleton): AbstractCrontab
    {
        $this->singleton = $singleton;
        return $this;
    }

    public function getMutexPool(): string
    {
        return $this->mutexPool;
    }

    public function setMutexPool(string $mutexPool): AbstractCrontab
    {
        $this->mutexPool = $mutexPool;
        return $this;
    }

    public function getMutexExpires(): int
    {
        return $this->mutexExpires;
    }

    public function setMutexExpires(int $mutexExpires): AbstractCrontab
    {
        $this->mutexExpires = $mutexExpires;
        return $this;
    }

    public function isOnOneServer(): bool
    {
        return $this->onOneServer;
    }

    public function setOnOneServer(bool $onOneServer): AbstractCrontab
    {
        $this->onOneServer = $onOneServer;
        return $this;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function setCallback($callback): AbstractCrontab
    {
        $this->callback = $callback;
        return $this;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setMemo(?string $memo): AbstractCrontab
    {
        $this->memo = $memo;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return AbstractCrontab
     */
    public function setType(string $type): AbstractCrontab
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return Carbon|null
     */
    public function getExecuteTime(): ?Carbon
    {
        return $this->executeTime;
    }

    /**
     * @param Carbon $executeTime
     * @return AbstractCrontab
     */
    public function setExecuteTime(Carbon $executeTime): AbstractCrontab
    {
        $this->executeTime = $executeTime;
        return $this;
    }

    /**
     * 0 * * * * *
     *
     * @return $this
     */
    public function minutely()
    {
        $rule = $this->getRuleArr();
        $rule[0] = 0;

        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * %d * * * * *
     *
     * @param $second
     * @return $this
     */
    public function minutelyAt($second)
    {
        $rule = $this->getRuleArr();

        if ($second < 0) {
            $second = 0;
        }

        if ($second > 59) {
            $second = 59;
        }

        $rule[0] = (int)$second;

        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * 0 0 * * * *
     *
     * @return $this
     */
    public function hourly()
    {
        $rule = $this->getRuleArr();

        if ($rule[0] == '*') {
            $rule[0] = 0;
        }

        $rule[1] = 0;

        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * 0 %d * * * *
     *
     * @param $minute
     * @return $this
     */
    public function hourlyAt($minute)
    {
        $rule = $this->getRuleArr();

        if (false !== strpos($minute, ':')) {
            [$minute, $second] = explode(':', $minute, 2);
            if ((int)$second < 0) {
                $second = 0;
            }
            if ((int)$second > 59) {
                $second = 59;
            }
            $rule[0] = (int)$second;
        } else {
            if ($rule[0] == '*') {
                $rule[0] = 0;
            }
        }

        if ($minute < 0) {
            $minute = 0;
        }

        if ($minute > 59) {
            $minute = 59;
        }

        $rule[1] = (int)$minute;
        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * 0 0 0 * * *
     *
     * @return $this
     */
    public function daily()
    {
        $rule = $this->getRuleArr();

        if ($rule[0] == '*') {
            $rule[0] = 0;
        }
        if ($rule[1] == '*') {
            $rule[1] = 0;
        }

        $rule[2] = 0;

        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * 0 0 %d * * *
     *
     * @param $hour
     * @return $this
     * @throws Exception
     */
    public function dailyAt($hour)
    {
        $rule = $this->getRuleArr();

        if (false !== strpos($hour, ':')) {
            $arr = explode(':', $hour);
            if (count($arr) === 3) {
                [$hour, $minute, $second] = $arr;
                if ((int)$second < 0) {
                    $second = 0;
                }
                if ((int)$second > 59) {
                    $second = 59;
                }

                $rule[0] = (int)$second;
            } elseif (count($arr) == 2) {
                [$hour, $minute] = $arr;
                if ($rule[0] == '*') {
                    $rule[0] = 0;
                }
            } else {
                throw new Exception('格式错误');
            }

            if ((int)$minute < 0) {
                $minute = 0;
            }

            if ((int)$minute > 59) {
                $minute = 59;
            }

            if ((int)$hour < 0) {
                $hour = 0;
            }

            if ((int)$hour > 23) {
                $hour = 23;
            }
            $rule[1] = (int)$minute;
        } else {
            if ($rule[0] == '*') {
                $rule[0] = 0;
            }
            if ($rule[1] == '*') {
                $rule[1] = 0;
            }
        }
        $rule[2] = (int)$hour;
        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * 0 0 0 * * 1
     *
     * @return $this
     */
    public function weekly()
    {
        $rule = $this->getRuleArr();

        if ($rule[5] == '*') {
            $rule[5] = 1;
        }
        if ($rule[0] == '*') {
            $rule[0] = 0;
        }

        if ($rule[1] == '*') {
            $rule[1] = 0;
        }

        if ($rule[2] == '*') {
            $rule[2] = 0;
        }

        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * 0 0 0 * * %d
     *
     * @param int $day
     * @return $this
     */
    public function weeklyAt(int $day)
    {
        $rule = $this->getRuleArr();

        if ($day < 0) {
            $day = 0;
        }

        if ($day > 6) {
            $day = 6;
        }

        if ($rule[0] == '*') {
            $rule[0] = 0;
        }

        if ($rule[1] == '*') {
            $rule[1] = 0;
        }

        if ($rule[2] == '*') {
            $rule[2] = 0;
        }

        $rule[5] = $day;
        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * 0 0 0 1 * *
     *
     * @return $this
     */
    public function monthly()
    {
        $rule = $this->getRuleArr();

        if ($rule[0] == '*') {
            $rule[0] = 0;
        }

        if ($rule[1] == '*') {
            $rule[1] = 0;
        }

        if ($rule[2] == '*') {
            $rule[2] = 0;
        }

        $rule[3] = 1;
        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * 0 0 0 %d * *
     *
     * @param int $day
     * @return $this
     */
    public function monthlyAt(int $day)
    {
        $rule = $this->getRuleArr();

        if ($day < 1) {
            $day = 1;
        }

        if ($day > 31) {
            $day = 31;
        }

        if ($rule[0] == '*') {
            $rule[0] = 0;
        }

        if ($rule[1] == '*') {
            $rule[1] = 0;
        }

        if ($rule[2] == '*') {
            $rule[2] = 0;
        }

        $rule[3] = (int)$day;
        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * 0 0 0 1 1 *
     *
     * @return $this
     */
    public function yearly()
    {
        $rule = $this->getRuleArr();

        if ($rule[0] == '*') {
            $rule[0] = 0;
        }

        if ($rule[1] == '*') {
            $rule[1] = 0;
        }

        if ($rule[2] == '*') {
            $rule[2] = 0;
        }

        if ($rule[3] == '*') {
            $rule[3] = 1;
        }

        if ($rule[4] == '*') {
            $rule[4] = 1;
        }

        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * 0 0 0 1 %d *
     *
     * @param int $month
     * @return $this
     */
    public function yealyAt(int $month)
    {
        $rule = $this->getRuleArr();

        if ($month < 1) {
            $month = 1;
        }

        if ($month > 12) {
            $month = 12;
        }

        if ($rule[0] == '*') {
            $rule[0] = 0;
        }

        if ($rule[1] == '*') {
            $rule[1] = 0;
        }

        if ($rule[2] == '*') {
            $rule[2] = 0;
        }

        if ($rule[3] == '*') {
            $rule[3] = 1;
        }

        $rule[4] = (int)$month;

        $this->setRule(implode(' ', $rule));
        return $this;
    }

    /**
     * @return array
     */
    protected function getRuleArr()
    {
        return explode(' ', $this->rule);
    }
}
