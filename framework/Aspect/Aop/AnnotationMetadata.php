<?php

namespace Laras\Aspect\Aop;

class AnnotationMetadata
{
    public $class = [];

    public $method = [];

    public function __construct(array $class, array $method)
    {
        $this->class = $class;
        $this->method = $method;
    }
}
