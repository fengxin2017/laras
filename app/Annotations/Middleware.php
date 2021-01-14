<?php

namespace App\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Middleware
 * @Annotation
 * @Target({"METHOD"})
 * @package App\Annotations
 *
 */
class Middleware
{
    public $middlewares = [];

    /**
     * Middleware constructor.
     * @param array $middlewares
     */
    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }
}