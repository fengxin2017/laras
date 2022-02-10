<?php


namespace Laras\Support\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Aspect
 * @Annotation
 * @Target({"CLASS"})
 * @package Laras\Aspect\Annotation
 */
class Aspect
{
    /**
     * @var array
     */
    public $classes = [];

    /**
     * @var array
     */
    public $annotations = [];

    /**
     * @var null|int
     */
    public $priority;
}