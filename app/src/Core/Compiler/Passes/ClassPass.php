<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler\Passes;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;
use PrestoWorld\Core\Compiler\CompilationContext;
use PrestoWorld\Core\Compiler\CompileIssue;
use PrestoWorld\Core\Compiler\CompileReport;
use PrestoWorld\Core\Compiler\Mapping\ClassMapping;
use PrestoWorld\Core\Compiler\Mapping\MappingRegistry;

/**
 * Pass 3 — ClassTransformer (§10.2.1, §10.5): WP_* classes → PW classes
 * trên New_, static call, type hints, extends/implements, instanceof, catch.
 */
final class ClassPass extends NodeVisitorAbstract implements CompilablePass
{
    public function __construct(private readonly CompilationContext $context)
    {
    }

    /** @var array<string, true> đã ghi issue structural cho symbol */
    private array $structuralNoted = [];

    public function leaveNode(Node $node): Node|null
    {
        if ($node instanceof Expr\New_) {
            return $this->rewriteNew($node);
        }

        if ($node instanceof Expr\StaticCall && $node->class instanceof Name) {
            $name = $this->rewriteName($node->class);
            if ($name !== null) {
                $node->class = $name;
            }

            return null;
        }

        if ($node instanceof Expr\ClassConstFetch && $node->class instanceof Name) {
            $name = $this->rewriteName($node->class);
            if ($name !== null) {
                $node->class = $name;
            }

            return null;
        }

        if ($node instanceof Expr\Instanceof_ && $node->class instanceof Name) {
            $name = $this->rewriteName($node->class);
            if ($name !== null) {
                $node->class = $name;
            }

            return null;
        }

        if ($node instanceof Expr\StaticPropertyFetch && $node->class instanceof Name) {
            $name = $this->rewriteName($node->class);
            if ($name !== null) {
                $node->class = $name;
            }

            return null;
        }

        if ($node instanceof Stmt\Catch_) {
            foreach ($node->types as $index => $type) {
                if ($type instanceof Name) {
                    $name = $this->rewriteName($type);
                    if ($name !== null) {
                        $node->types[$index] = $name;
                    }
                }
            }

            return null;
        }

        if ($node instanceof Stmt\Class_) {
            if ($node->extends instanceof Name) {
                $name = $this->rewriteName($node->extends);
                if ($name !== null) {
                    $node->extends = $name;
                }
            }
            foreach ($node->implements as $index => $interface) {
                $name = $this->rewriteName($interface);
                if ($name !== null) {
                    $node->implements[$index] = $name;
                }
            }

            return null;
        }

        if ($node instanceof Node\Param) {
            if ($node->type !== null) {
                $node->type = $this->rewriteType($node->type);
            }

            return null;
        }

        if ($node instanceof Node\FunctionLike) {
            if ($node->returnType !== null) {
                $node->returnType = $this->rewriteType($node->returnType);
            }

            return null;
        }

        return null;
    }

    private function rewriteNew(Expr\New_ $node): ?Node
    {
        if (!($node->class instanceof Name)) {
            return null;
        }

        $className = $node->class->toString();
        if ($this->context->symbols->isPluginClass($className)) {
            return null;
        }

        $mapping = $this->context->mappings->classFor($className);
        if ($mapping === null) {
            // Không phải WP class — giữ nguyên.
            return $this->looksLikeWordPressClass($className) ? $node : null;
        }

        if ($mapping->isUnsupported()) {
            $this->noteUnresolved($className, $node->getStartLine());
            $this->noteStructural($mapping, $className, $node->getStartLine());

            return null;
        }

        if ($mapping->isNoop()) {
            return new Expr\ConstFetch(new Name('null'));
        }

        if (!$mapping->supportsRewrite()) {
            return null;
        }

        $this->noteStructural($mapping, $className, $node->getStartLine());
        $this->context->report->incrementStat(CompileReport::STAT_CLASSES_MAPPED);

        return new Expr\New_(
            new Name\FullyQualified($mapping->target),
            $node->args,
        );
    }

    /**
     * Rewrite name trong type hints / extends / implements / instanceof...
     * Trả về null nếu không có gì cần đổi.
     */
    private function rewriteName(Name $name): ?Name
    {
        $className = $name->toString();

        if ($this->context->symbols->isPluginClass($className)) {
            return null;
        }

        $mapping = $this->context->mappings->classFor($className);
        if ($mapping === null) {
            return null;
        }

        if ($mapping->isUnsupported()) {
            $this->noteUnresolved($className, $name->getStartLine());
            $this->noteStructural($mapping, $className, $name->getStartLine());

            return null;
        }

        if (!$mapping->supportsRewrite()) {
            return null;
        }

        $this->noteStructural($mapping, $className, $name->getStartLine());
        $this->context->report->incrementStat(CompileReport::STAT_CLASSES_MAPPED);

        return new Name\FullyQualified($mapping->target);
    }

    private function rewriteType(Node\ComplexType|Node\Identifier|Node\Name $type): Node\ComplexType|Node\Identifier|Node\Name
    {
        if ($type instanceof Name) {
            return $this->rewriteName($type) ?? $type;
        }

        if ($type instanceof Node\NullableType) {
            if ($type->type instanceof Name) {
                $name = $this->rewriteName($type->type);
                if ($name !== null) {
                    $type->type = $name;
                }
            }

            return $type;
        }

        if (
            $type instanceof Node\UnionType
            || $type instanceof Node\IntersectionType
        ) {
            foreach ($type->types as $index => $inner) {
                if ($inner instanceof Name) {
                    $name = $this->rewriteName($inner);
                    if ($name !== null) {
                        $type->types[$index] = $name;
                    }
                }
            }

            return $type;
        }

        return $type;
    }

    private function noteUnresolved(string $className, ?int $line = null): void
    {
        if (isset($this->structuralNoted['unresolved:' . $className])) {
            return;
        }
        $this->structuralNoted['unresolved:' . $className] = true;

        $this->context->report->addIssue(new CompileIssue(
            type: CompileIssue::UNRESOLVED_CLASS,
            symbol: $className,
            file: $this->context->currentFile(),
            line: $line,
            reason: 'chưa có PW equivalent — dựa vào shim autoload',
            fallback: 'shim',
        ));
    }

    private function noteStructural(ClassMapping $mapping, string $className, ?int $line = null): void
    {
        if ($mapping->structural === [] || isset($this->structuralNoted['structural:' . $className])) {
            return;
        }
        $this->structuralNoted['structural:' . $className] = true;

        $details = [];
        foreach ($mapping->structural as $key => $value) {
            $details[] = $key . '=' . $value;
        }

        $this->context->report->addIssue(new CompileIssue(
            type: CompileIssue::STRUCTURAL_CLASS,
            symbol: $className,
            file: $this->context->currentFile(),
            line: $line,
            reason: 'structural adaptation cần thiết: ' . implode(', ', $details),
            fallback: 'adapter',
        ));
    }

    private function looksLikeWordPressClass(string $className): bool
    {
        return str_starts_with($className, 'WP_') || str_starts_with($className, 'wpdb')
            || str_starts_with($className, 'WP');
    }
}