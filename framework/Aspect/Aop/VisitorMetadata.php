<?php

namespace Laras\Aspect\Aop;

use PhpParser\Node;

class VisitorMetadata
{
    /**
     * @var string $className
     */
    public $className;

    /**
     * @var bool $hasConstructor
     */
    public $hasConstructor;

    /**
     * @var null|Node\Stmt\ClassMethod $constructorNode
     */
    public $constructorNode;

    /**
     * @var bool $hasExtends
     */
    public $hasExtends;

    /**
     * @var null|string $classLike
     */
    public $classLike;
}
