<?php


namespace Laras\Support\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Inject
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Inject
{
    /**
     * @var bool
     */
    public $required = true;


    public $inject = null;

    /**
     * Inject constructor.
     * @param $inject
     */
    public function __construct($inject)
    {
        $this->inject = $inject;
    }
}