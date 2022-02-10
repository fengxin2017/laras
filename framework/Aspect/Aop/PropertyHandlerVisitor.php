<?php

namespace Laras\Aspect\Aop;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;
use ReflectionException;

class PropertyHandlerVisitor extends NodeVisitorAbstract
{
    /**
     * @var array
     */
    protected $proxyTraits = [
        PropertyHandlerTrait::class,
    ];

    /**
     * @var VisitorMetadata
     */
    protected $visitorMetadata;

    public function __construct(VisitorMetadata $visitorMetadata)
    {
        $this->visitorMetadata = $visitorMetadata;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            if ($this->visitorMetadata->hasExtends === null) {
                if ($node->extends) {
                    $this->visitorMetadata->hasExtends = true;
                } else {
                    $this->visitorMetadata->hasExtends = false;
                }
            }
        }
        if ($node instanceof Node\Stmt\ClassMethod) {
            if ($node->name->toString() === '__construct') {
                $this->visitorMetadata->hasConstructor = true;
                $this->visitorMetadata->constructorNode = $node;
            }
        }
        return null;
    }

    /**
     * @param Node $node
     * @return int|Node|Node[]|null
     * @throws ReflectionException
     */
    public function leaveNode(Node $node)
    {
        if (!$this->visitorMetadata->hasConstructor && $node instanceof Node\Stmt\Class_ && !$node->isAnonymous()) {
            $constructor = $this->buildConstructor();
            if ($this->visitorMetadata->hasExtends) {
                $constructor->stmts[] = $this->buildCallParentConstructorStatement();
            }
            $constructor->stmts[] = $this->buildMethodCallStatement();
            $node->stmts = array_merge([$this->buildProxyTraitUseStatement()], [$constructor], $node->stmts);
            $this->visitorMetadata->hasConstructor = true;
        } else {
            if ($node instanceof Node\Stmt\ClassMethod && $node->name->toString() === '__construct') {
                $node->stmts = array_merge([$this->buildMethodCallStatement()], $node->stmts);
            }
            if ($node instanceof Node\Stmt\Class_ && !$node->isAnonymous()) {
                $node->stmts = array_merge([$this->buildProxyTraitUseStatement()], $node->stmts);
            }
        }
        return null;
    }

    /**
     * @return Node\Stmt\ClassMethod
     * @throws ReflectionException
     */
    protected function buildConstructor(): Node\Stmt\ClassMethod
    {
        if ($this->visitorMetadata->constructorNode instanceof Node\Stmt\ClassMethod) {
            // Returns the parsed constructor class method node.
            $constructor = $this->visitorMetadata->constructorNode;
        } else {
            // Create a new constructor class method node.
            $constructor = new Node\Stmt\ClassMethod('__construct');
            $reflection = new ReflectionClass($this->visitorMetadata->className);
            try {
                $parameters = $reflection->getMethod('__construct')->getParameters();
                foreach ($parameters as $parameter) {
                    $constructor->params[] = PhpParser::getInstance()->getNodeFromReflectionParameter($parameter);
                }
            } catch (\ReflectionException $exception) {
                // Cannot found __construct method in parent class or traits, do noting.
            }
        }
        return $constructor;
    }

    protected function buildCallParentConstructorStatement(): Node\Stmt
    {
        $hasConstructor = new Node\Expr\FuncCall(new Name('method_exists'), [
            new Node\Arg(new Node\Expr\ClassConstFetch(new Name('parent'), 'class')),
            new Node\Arg(new Node\Scalar\String_('__construct')),
        ]);
        return new Node\Stmt\If_($hasConstructor, [
            'stmts' => [
                new Node\Stmt\Expression(new Node\Expr\StaticCall(new Name('parent'), '__construct', [
                    new Node\Arg(new Node\Expr\FuncCall(new Name('func_get_args')), false, true),
                ])),
            ],
        ]);
    }

    protected function buildMethodCallStatement(): Node\Stmt\Expression
    {
        return new Node\Stmt\Expression(new Node\Expr\MethodCall(new Node\Expr\Variable('this'), '__handlePropertyHandler', [
            new Node\Arg(new Node\Scalar\MagicConst\Class_()),
        ]));
    }

    /**
     * Build `use PropertyHandlerTrait;` statement.
     */
    protected function buildProxyTraitUseStatement(): TraitUse
    {
        $traits = [];
        foreach ($this->proxyTraits as $proxyTrait) {
            // Should not check the trait whether or not exist to avoid class autoload.
            if (!is_string($proxyTrait)) {
                continue;
            }
            // Add backslash prefix if the proxy trait does not start with backslash.
            $proxyTrait[0] !== '\\' && $proxyTrait = '\\' . $proxyTrait;
            $traits[] = new Name($proxyTrait);
        }
        return new TraitUse($traits);
    }
}