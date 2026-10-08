<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

/**
 * Stage 3 pipeline (§10.2.1): quét AST toàn plugin, lập symbol table
 * cho Pass 2 (pluggable override) và Pass 4 (user fn → static method).
 *
 * Một instance tích lũy qua nhiều file (Phase A), sau đó build() một lần.
 */
final class SymbolAnalyzer
{
    /** @var array<string, FunctionSymbol> */
    private array $functions = [];

    /** @var array<string, ClassSymbol> */
    private array $classes = [];

    /**
     * @param array<Node\Stmt> $ast
     */
    public function analyze(string $relativeFile, array $ast): void
    {
        $this->walk($ast, [
            'file' => $relativeFile,
            'fnDepth' => 0,
            'conditional' => false,
            'guards' => [],
            'namespace' => null,
        ]);
    }

    public function build(string $slug, string $type): SymbolTable
    {
        return new SymbolTable($slug, $type, $this->functions, $this->classes);
    }

    /**
     * @param array<string, mixed> $state
     */
    private static function intState(array $state, string $key): int
    {
        $value = $state[$key] ?? 0;

        return is_int($value) ? $value : 0;
    }

    /**
     * @param array<string, mixed> $state
     */
    private static function boolState(array $state, string $key): bool
    {
        $value = $state[$key] ?? false;

        return is_bool($value) ? $value : false;
    }

    /**
     * @param array<string, mixed> $state
     */
    private static function strState(array $state, string $key): string
    {
        $value = $state[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return list<string>
     */
    private static function listState(array $state, string $key): array
    {
        $value = $state[$key] ?? [];

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    private static function stateWithDepth(array $state, int $by): array
    {
        return ['fnDepth' => self::intState($state, 'fnDepth') + $by] + $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function walk(mixed $node, array $state): void
    {
        if (is_array($node)) {
            foreach ($node as $child) {
                $this->walk($child, $state);
            }

            return;
        }

        if (!$node instanceof Node) {
            return;
        }

        if ($node instanceof Stmt\Function_) {
            $this->handleFunction($node, $state);

            return;
        }

        if ($node instanceof Node\Stmt\ClassLike) {
            $this->recordClass($node, $state);
            $this->walkChildren($node, self::stateWithDepth($state, 1));

            return;
        }

        if ($node instanceof Stmt\If_) {
            /** @var list<string> $baseGuards */
            $baseGuards = $state['guards'];
            $condGuards = array_merge($baseGuards, $this->functionExistsNames($node->cond));
            $this->walk($node->cond, $state);

            $bodyState = [
                'conditional' => true,
                'guards' => $condGuards,
            ] + $state;
            foreach ($node->stmts as $stmt) {
                $this->walk($stmt, $bodyState);
            }

            foreach ($node->elseifs as $elseif) {
                $elseifGuards = array_merge($baseGuards, $this->functionExistsNames($elseif->cond));
                $this->walk($elseif->cond, $state);
                foreach ($elseif->stmts as $stmt) {
                    $this->walk($stmt, ['conditional' => true, 'guards' => $elseifGuards] + $state);
                }
            }

            if ($node->else !== null) {
                foreach ($node->else->stmts as $stmt) {
                    $this->walk($stmt, ['conditional' => true, 'guards' => $baseGuards] + $state);
                }
            }

            return;
        }

        if ($node instanceof Stmt\Switch_) {
            $this->walk($node->cond, $state);
            $caseState = ['conditional' => true] + $state;
            foreach ($node->cases as $case) {
                $this->walk($case, $caseState);
            }

            return;
        }

        if ($node instanceof Stmt\While_ || $node instanceof Stmt\Do_) {
            $this->walk($node->cond, $state);
            foreach ($node->stmts as $stmt) {
                $this->walk($stmt, ['conditional' => true] + $state);
            }

            return;
        }

        if ($node instanceof Stmt\For_) {
            foreach ($node->init as $init) {
                $this->walk($init, $state);
            }
            foreach ($node->cond as $cond) {
                $this->walk($cond, $state);
            }
            foreach ($node->loop as $loop) {
                $this->walk($loop, $state);
            }
            foreach ($node->stmts as $stmt) {
                $this->walk($stmt, ['conditional' => true] + $state);
            }

            return;
        }

        if ($node instanceof Stmt\Foreach_) {
            $this->walk($node->expr, $state);
            $this->walk($node->valueVar, $state);
            if ($node->keyVar !== null) {
                $this->walk($node->keyVar, $state);
            }
            foreach ($node->stmts as $stmt) {
                $this->walk($stmt, ['conditional' => true] + $state);
            }

            return;
        }

        if ($node instanceof Stmt\Namespace_) {
            $namespace = $node->name !== null ? $node->name->toString() : null;
            $this->walk($node->stmts, ['namespace' => $namespace] + $state);

            return;
        }

        $this->walkChildren($node, $state);
    }

    /**
     * @param array<string, mixed> $state
     */
    private function walkChildren(Node $node, array $state): void
    {
        foreach ($node->getSubNodeNames() as $name) {
            /** @var mixed $sub */
            $sub = $node->{$name};
            if (is_array($sub)) {
                foreach ($sub as $child) {
                    $this->walk($child, $state);
                }
            } elseif ($sub instanceof Node) {
                $this->walk($sub, $state);
            }
        }
    }

    /**
     * @param array<string, mixed> $state
     */
    private function handleFunction(Stmt\Function_ $fn, array $state): void
    {
        $name = $fn->name->toString();
        $lower = strtolower($name);
        $guards = self::listState($state, 'guards');
        $file = self::strState($state, 'file');
        $namespace = self::strState($state, 'namespace');

        $symbol = new FunctionSymbol(
            name: $name,
            file: $file,
            line: $fn->getStartLine(),
            isNested: self::intState($state, 'fnDepth') > 0,
            isConditional: self::boolState($state, 'conditional'),
            hasFunctionExistsGuard: in_array($lower, $guards, true),
            namespace: $namespace !== '' ? $namespace : null,
        );

        [$symbol->usesExtract, $symbol->usesCompact] = $this->scanExtractFlags($fn->stmts);

        $this->functions[$lower] = $symbol;

        $this->walkChildren($fn, self::stateWithDepth($state, 1));
    }

    /**
     * @param array<string, mixed> $state
     */
    private function recordClass(Node\Stmt\ClassLike $class, array $state): void
    {
        if ($class->name === null) {
            return;
        }

        $kind = match (true) {
            $class instanceof Stmt\Class_ => 'class',
            $class instanceof Stmt\Interface_ => 'interface',
            $class instanceof Stmt\Trait_ => 'trait',
            $class instanceof Stmt\Enum_ => 'enum',
            default => 'class',
        };

        $name = $class->name->toString();
        $namespace = self::strState($state, 'namespace');
        $this->classes[strtolower($name)] = new ClassSymbol(
            name: $name,
            file: self::strState($state, 'file'),
            line: $class->getStartLine(),
            kind: $kind,
            namespace: $namespace !== '' ? $namespace : null,
        );
    }

    /**
     * Phát hiện extract()/compact() trong body — KHÔNG đếm nested
     * function/class/closure (scope riêng, không ảnh hưởng hàm ngoài).
     *
     * @param array<Node> $nodes
     *
     * @return array{0: bool, 1: bool}
     */
    private function scanExtractFlags(array $nodes): array
    {
        $extract = false;
        $compact = false;

        $visit = function (mixed $node) use (&$visit, &$extract, &$compact): void {
            if (is_array($node)) {
                foreach ($node as $child) {
                    $visit($child);
                }

                return;
            }

            if (!$node instanceof Node) {
                return;
            }

            if (
                $node instanceof Stmt\Function_
                || $node instanceof Node\Stmt\ClassLike
                || $node instanceof Expr\Closure
                || $node instanceof Expr\ArrowFunction
            ) {
                return;
            }

            if (
                $node instanceof Expr\FuncCall
                && $node->name instanceof Node\Name
                && $node->name->isUnqualified()
            ) {
                $fn = strtolower($node->name->toString());
                if ($fn === 'extract') {
                    $extract = true;
                }
                if ($fn === 'compact') {
                    $compact = true;
                }
            }

            foreach ($node->getSubNodeNames() as $subName) {
                /** @var mixed $sub */
                $sub = $node->{$subName};
                if (is_array($sub) || $sub instanceof Node) {
                    $visit($sub);
                }
            }
        };

        $visit($nodes);

        return [$extract, $compact];
    }

    /**
     * Tên hàm được kiểm tra bởi function_exists() trong điều kiện if.
     *
     * @return list<string>
     */
    private function functionExistsNames(mixed $node): array
    {
        $names = [];

        $visit = function (mixed $node) use (&$visit, &$names): void {
            if (is_array($node)) {
                foreach ($node as $child) {
                    $visit($child);
                }

                return;
            }

            if (!$node instanceof Node) {
                return;
            }

            if (
                $node instanceof Expr\FuncCall
                && $node->name instanceof Node\Name
                && $node->name->isUnqualified()
                && strtolower($node->name->toString()) === 'function_exists'
                && isset($node->args[0])
                && $node->args[0]->value instanceof Node\Scalar\String_
            ) {
                $names[] = strtolower($node->args[0]->value->value);
            }

            foreach ($node->getSubNodeNames() as $subName) {
                /** @var mixed $sub */
                $sub = $node->{$subName};
                if (is_array($sub) || $sub instanceof Node) {
                    $visit($sub);
                }
            }
        };

        $visit($node);

        return $names;
    }
}
