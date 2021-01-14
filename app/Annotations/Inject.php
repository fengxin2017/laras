<?php


namespace App\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Inject
 * @Annotation
 * @Target({"PROPERTY"})
 * @package App\Inject
 */
class Inject
{

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