<?php

namespace MoneyMaker\Crontab;

class CrontabManager
{
    /**
     * @var AbstractCrontab[]
     */
    protected $crontabs = [];

    /**
     * @var Parser
     */
    protected $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function register(AbstractCrontab $crontab): bool
    {
        if (! $this->isValidCrontab($crontab)) {
            return false;
        }
        $this->crontabs[$crontab->getName()] = $crontab;
        return true;
    }

    public function parse(): array
    {
        $result = [];
        $crontabs = $this->getCrontabs();
        $last = time();
        foreach ($crontabs ?? [] as $key => $crontab) {
            if (! $crontab instanceof AbstractCrontab) {
                unset($this->crontabs[$key]);
                continue;
            }
            $time = $this->parser->parse($crontab->getRule(), $last);
            if ($time) {
                foreach ($time as $t) {
                    $result[] = clone $crontab->setExecuteTime($t);
                }
            }
        }
        return $result;
    }

    public function getCrontabs(): array
    {
        return $this->crontabs;
    }

    private function isValidCrontab(AbstractCrontab $crontab): bool
    {
        return $crontab->getName() && $crontab->getRule() && $this->parser->isValid($crontab->getRule());
    }
}
