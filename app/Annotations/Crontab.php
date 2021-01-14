<?php


namespace App\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Crontab
 * @Annotation
 * @Target({"CLASS"})
 * @package App\Annotations
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