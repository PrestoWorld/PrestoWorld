<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler\Passes;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PrestoWorld\Core\Compiler\CompilationContext;
use PrestoWorld\Core\Compiler\CompileIssue;
use PrestoWorld\Core\Compiler\CompileReport;
use PrestoWorld\Core\Compiler\FunctionSymbol;

/**
 * Pass 4 — UserFunctionTransformer (§10.2.1, §10.6): user functions →
 * static methods trên class PSR-4 sinh theo file (§10.6.2).
 *
 * - Function_ được trích xuất ra file class riêng (WpCompiler emit sau traverse).
 * - Call sites + string callbacks → static call / [Class::class, 'method'].
 * - function_exists('fn') → method_exists(Class::class, 'method') (pluggable guard).
 */
final class UserFunctionPass extends NodeVisitorAbstract implements CompilablePass
{
    private const HOOK_CALLABLE_FUNCTIONS = [
        'add_action', 'add_filter', 'remove_action', 'remove_filter',
        'has_action', 'has_filter', 'add_shortcode', 'call_user_func',
        'call_user_func_array', 'array_map', 'array_filter', 'array_walk',
        'array_walk_recursive', 'usort', 'uasort', 'uksort',
        'preg_replace_callback', 'register_shutdown_function',
        'set_error_handler', 'set_exception_handler', 'wp_add_inline_script',
    ];

    public function __construct(private readonly CompilationContext $context)
    {
    }

    /**
     * @var array<string, array{class: string, method: string, symbol: FunctionSymbol}>
     *   lowercase fn → target đã lên static method
     */
    private array $extracted = [];

    /** @var array<string, list<Stmt\ClassMethod>> file → methods trích xuất */
    private array $classMethods = [];

    public function beforeTraverse(array $nodes): ?array
    {
        $this->classMethods = [];
        $this->extracted = [];

        foreach ($this->context->symbols->functions() as $symbol) {
            if (!$symbol->isExtractable()) {
                continue;
            }
            $target = $this->context->symbols->targetFor($symbol);
            [$fqcn, $method] = explode('::', $target, 2);
            $lower = strtolower($symbol->name);
            $this->extracted[$lower] = [
                'class' => $fqcn,
                'method' => $method,
                'symbol' => $symbol,
            ];
        }

        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        // function_exists('myplugin_fn') → method_exists(Plugins\X\Cls::class, 'method')
        if (
            $node instanceof Expr\FuncCall
            && $node->name instanceof Name
            && strtolower($node->name->toString()) === 'function_exists'
            && isset($node->args[0])
            && $node->args[0]->value instanceof Scalar\String_
        ) {
            $target = $this->extracted[strtolower($node->args[0]->value->value)] ?? null;
            if ($target !== null) {
                return new Expr\FuncCall(
                    new Name\FullyQualified('method_exists'),
                    [
                        new Node\Arg(new Expr\ClassConstFetch(new Name\FullyQualified($target['class']), 'class')),
                        new Node\Arg(new Scalar\String_($target['method'])),
                    ],
                );
            }
        }

        return null;
    }

    public function leaveNode(Node $node): Node|int|null
    {
        if ($node instanceof Stmt\Function_) {
            return $this->extractFunction($node);
        }

        if ($node instanceof Expr\FuncCall && $node->name instanceof Name) {
            $lower = strtolower($node->name->toString());

            // Call site user fn → static call (§10.6.4)
            $target = $this->extracted[$lower] ?? null;
            if ($target !== null) {
                return new Expr\StaticCall(
                    new Name\FullyQualified($target['class']),
                    $target['method'],
                    $node->args,
                );
            }

            // String callback bên trong callable functions → [Class::class, 'method']
            if (in_array($lower, self::HOOK_CALLABLE_FUNCTIONS, true)) {
                foreach ($node->args as $argIndex => $arg) {
                    if ($arg->value instanceof Scalar\String_) {
                        $resolved = $this->extracted[strtolower($arg->value->value)] ?? null;
                        if ($resolved !== null) {
                            $node->args[$argIndex]->value = $this->arrayCallable($resolved);
                        }
                    }
                }
            }

            return null;
        }

        if ($node instanceof Scalar\String_) {
            $target = $this->extracted[strtolower($node->value)] ?? null;
            if ($target !== null) {
                return $this->arrayCallable($target);
            }

            return null;
        }

        // If_ rỗng sau khi Function_ được trích xuất (vd: if (!function_exists(...)) {}).
        if (
            $node instanceof Stmt\If_
            && $node->stmts === []
            && $node->elseifs === []
            && $node->else === null
        ) {
            return NodeVisitor::REMOVE_NODE;
        }

        return null;
    }

    /**
     * @return array<string, list<Stmt\ClassMethod>>
     */
    public function extractedClassMethods(): array
    {
        return $this->classMethods;
    }

    private function extractFunction(Stmt\Function_ $fn): ?int
    {
        $symbol = $this->context->symbols->functionFor($fn->name->toString());
        if ($symbol === null) {
            return null;
        }

        if (!$symbol->isExtractable()) {
            if ($symbol->isLeftover()) {
                $this->context->report->incrementStat(CompileReport::STAT_USER_FUNCTIONS_LEFTOVER);
            } elseif ($symbol->skipReason() !== null) {
                $this->context->report->incrementStat(CompileReport::STAT_USER_FUNCTIONS_SKIPPED);
                $this->context->report->addIssue(new CompileIssue(
                    type: CompileIssue::USER_FN_SKIPPED,
                    symbol: $symbol->name,
                    file: $symbol->file,
                    line: $symbol->line,
                    reason: $symbol->skipReason(),
                ));
            }

            return null;
        }

        $target = $this->context->symbols->targetFor($symbol);
        [$class, $method] = explode('::', $target, 2);

        $this->classMethods[$symbol->file][] = $this->toClassMethod($fn, $method);

        $this->context->report->incrementStat(CompileReport::STAT_USER_FUNCTIONS_COMPILED);
        $this->context->report->mapUserFunction($symbol->name, $target);

        if ($symbol->hasPluggableGuard()) {
            $this->context->report->addIssue(new CompileIssue(
                type: CompileIssue::PLUGGABLE_FUNCTION,
                symbol: $symbol->name,
                file: $symbol->file,
                line: $symbol->line,
                reason: 'định nghĩa có function_exists guard — compile thành method_exists',
            ));
        }

        return NodeVisitor::REMOVE_NODE;
    }

    private function toClassMethod(Stmt\Function_ $fn, string $method): Stmt\ClassMethod
    {
        return new Stmt\ClassMethod($method, [
            'flags' => Stmt\Class_::MODIFIER_PUBLIC | Stmt\Class_::MODIFIER_STATIC,
            'byRef' => $fn->byRef,
            'params' => $fn->params,
            'returnType' => $fn->returnType,
            'stmts' => $fn->stmts,
            'attrGroups' => $fn->attrGroups,
        ], $fn->getAttributes());
    }

    /**
     * @param array{class: string, method: string} $target
     */
    private function arrayCallable(array $target): Expr\Array_
    {
        return new Expr\Array_([
            new Expr\ArrayItem(
                new Expr\ClassConstFetch(new Name\FullyQualified($target['class']), 'class'),
            ),
            new Expr\ArrayItem(new Scalar\String_($target['method'])),
        ]);
    }
}