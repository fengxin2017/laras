<?php

namespace Laras\Aspect\Aop;

use PhpParser\Node;

class VisitorMetadata
{
    /**
     * @var string
     */
    public $className;

    /**
     * @var bool
     */
    public $hasConstructor;

    /**
     * @var null|Node\Stmt\ClassMethod
     */
    public $constructorNode;

    /**
     * @var bool
     */
    public $hasExtends;

    /**
     * @var null|string
     */
    public $classLike;
}
