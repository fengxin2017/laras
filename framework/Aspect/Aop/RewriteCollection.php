<?php

namespace Laras\Aspect\Aop;

class RewriteCollection
{
    public const CLASS_LEVEL = 1;

    public const METHOD_LEVEL = 2;

    /**
     * Which methods can be rewrite.
     * @var array
     */
    protected $methods = [];

    /**
     * Method pattern.
     * @var array
     */
    protected $pattern = [];

    /**
     * Rewrite level.
     * @var int
     */
    protected $level = self::METHOD_LEVEL;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var array
     */
    protected $shouldNotRewriteMethods = [
        '__construct',
    ];

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * @param $methods
     * @return $this
     */
    public function add($methods): self
    {
        $methods = (array) $methods;
        foreach ($methods as $method) {
            if (strpos($method, '*') === false) {
                $this->methods[] = $method;
            } else {
                $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $method);
                $this->pattern[] = "/^{$preg}$/";
            }
        }

        return $this;
    }

    public function shouldRewrite(string $method): bool
    {
        if ($this->level === self::CLASS_LEVEL) {
            if (in_array($method, $this->shouldNotRewriteMethods)) {
                return false;
            }
            return true;
        }

        if (in_array($method, $this->methods)) {
            return true;
        }

        foreach ($this->pattern as $pattern) {
            if (preg_match($pattern, $method)) {
                return true;
            }
        }

        return false;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Return the methods that should not rewrite.
     */
    public function getShouldNotRewriteMethods(): array
    {
        return $this->shouldNotRewriteMethods;
    }
}
