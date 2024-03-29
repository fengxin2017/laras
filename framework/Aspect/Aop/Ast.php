<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Laras\Aspect\Aop;

use Composer\Autoload\ClassLoader;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

class Ast
{
    /**
     * @var \PhpParser\Parser
     */
    private $astParser;

    /**
     * @var PrettyPrinterAbstract
     */
    private $printer;

    public function __construct()
    {
        $parserFactory   = new ParserFactory();
        $this->astParser = $parserFactory->create(ParserFactory::ONLY_PHP7);
        $this->printer   = new Standard();
    }

    public function parse(string $code): ?array
    {
        return $this->astParser->parse($code);
    }

    public function proxy(string $className)
    {
        $code                       = $this->getCodeByClassName($className);
        $stmts                      = $this->astParser->parse($code);
        $traverser                  = new NodeTraverser();
        $visitorMetadata            = new VisitorMetadata();
        $visitorMetadata->className = $className;

        $queue = clone AstVisitorRegistry::getQueue();

        foreach ($queue as $string) {
            $visitor = new $string($visitorMetadata);
            $traverser->addVisitor($visitor);
        }
        $modifiedStmts = $traverser->traverse($stmts);
        return $this->printer->prettyPrintFile($modifiedStmts);
    }

    public function parseClassByStmts(array $stmts): string
    {
        $namespace = $className = '';
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Namespace_ && $stmt->name) {
                $namespace = $stmt->name->toString();
                foreach ($stmt->stmts as $node) {
                    if (($node instanceof Class_ || $node instanceof Interface_) && $node->name) {
                        $className = $node->name->toString();
                        break;
                    }
                }
            }
        }
        return ($namespace && $className) ? $namespace . '\\' . $className : '';
    }

    private function getCodeByClassName(string $className): string
    {
        $file = self::findLoader()
                    ->findFile($className);
        if (!$file) {
            return '';
        }
        return file_get_contents($file);
    }

    private static function findLoader(): ClassLoader
    {
        $composerClass = '';
        foreach (get_declared_classes() as $declaredClass) {
            if (strpos($declaredClass, 'ComposerAutoloaderInit') === 0 && method_exists($declaredClass, 'getLoader')) {
                $composerClass = $declaredClass;
                break;
            }
        }
        if (!$composerClass) {
            throw new \RuntimeException('Composer loader not found.');
        }
        return $composerClass::getLoader();
    }
}
