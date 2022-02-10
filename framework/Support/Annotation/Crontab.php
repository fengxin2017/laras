<?php


namespace Laras\Support\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Crontab
 * @Annotation
 * @Target({"CLASS"})
 */
class Crontab
{
    /**
     * @var mixed|null
     */
    public $name;

    /**
     * @var mixed|null
     */
    public $rule;

    /**
     * Crontab constructor.
     * @param array $value
     */
    public function __construct(array $value)
    {
        $this->name = $value['name'] ?? null;
        $this->rule = $value['rule'] ?? null;
    }
}