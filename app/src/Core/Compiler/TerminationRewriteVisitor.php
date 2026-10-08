<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;

/**
 * exit()/die() → throw LegacyTerminationException (an toàn với RoadRunner worker,
 * §10.2.4). Chỉ áp dụng khi đứng độc lập trong statement.
 */
final class TerminationRewriteVisitor extends NodeVisitorAbstract
{
    public const EXCEPTION_CLASS = 'PrestoWorld\Core\Legacy\LegacyTerminationException';

    public function enterNode(Node $node): ?Node
    {
        if (!($node instanceof Stmt\Expression) || !($node->expr instanceof Expr\Exit_)) {
            return null;
        }

        $status = $node->expr->expr;
        $args = [];
        if ($status !== null) {
            $args[] = new Arg($status);
        }

        return new Stmt\Expression(
            new Expr\Throw_(
                new Expr\New_(new Name\FullyQualified(self::EXCEPTION_CLASS), $args),
            ),
        );
    }
}