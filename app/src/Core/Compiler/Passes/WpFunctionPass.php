<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler\Passes;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\NodeVisitorAbstract;
use PrestoWorld\Core\Compiler\CompilationContext;
use PrestoWorld\Core\Compiler\CompileIssue;
use PrestoWorld\Core\Compiler\CompileReport;
use PrestoWorld\Core\Compiler\Mapping\FunctionMapping;

/**
 * Pass 2 — WpFunctionTransformer (§10.2.1, §10.4): ~400 WP functions
 * → PW services theo master mapping.
 */
final class WpFunctionPass extends NodeVisitorAbstract implements CompilablePass
{
    public function __construct(private readonly CompilationContext $context)
    {
    }

    /** @var array<string, true> internal PHP functions (lowercase) */
    private array $internalFunctions = [];

    public function beforeTraverse(array $nodes): ?array
    {
        $internal = get_defined_functions()['internal'];
        $this->internalFunctions = [];
        foreach ($internal as $name) {
            $this->internalFunctions[strtolower($name)] = true;
        }

        return null;
    }

    public function leaveNode(Node $node): Node|null
    {
        if (!($node instanceof Expr\FuncCall) || !($node->name instanceof Name)) {
            return null;
        }

        $lower = strtolower($node->name->toString());

        // Hàm do plugin tự định nghĩa → Pass 4 xử lý (pluggable override).
        if ($this->context->symbols->hasPluginFunction($lower)) {
            return null;
        }

        $mapping = $this->context->mappings->functionFor($lower);

        // Gọi hàm WP global từ namespace (NameResolver resolve thành FQ sai):
        // fallback theo segment cuối.
        if ($mapping === null && str_contains($lower, '\\')) {
            $parts = explode('\\', $lower);
            $last = (string) array_pop($parts);
            if ($last !== '') {
                $mapping = $this->context->mappings->functionFor($last);
                if ($mapping !== null) {
                    $lower = $last;
                }
            }
        }

        if ($mapping === null) {
            $this->handleUnknown($node, $lower);

            return null;
        }

        if ($mapping->isUnsupported()) {
            $this->context->report->addIssue(new CompileIssue(
                type: CompileIssue::UNSUPPORTED_FUNCTION,
                symbol: $mapping->source,
                file: $this->context->currentFile(),
                line: $node->getStartLine(),
                reason: $mapping->notes ?? 'unsupported',
                fallback: 'shim',
                group: $mapping->group,
            ));

            return null;
        }

        if ($mapping->isNoop()) {
            return new Expr\ConstFetch(new Name('null'));
        }

        // mode 's' (shim only): giữ nguyên call — runtime shim đảm nhiệm.
        if (!$mapping->supportsRewrite()) {
            return null;
        }

        $replacement = $this->buildReplacement($node, $mapping);

        $this->context->report->incrementStat(CompileReport::STAT_WP_FUNCTIONS_MAPPED);

        return $replacement;
    }

    private function handleUnknown(Expr\FuncCall $node, string $lower): void
    {
        if (isset($this->internalFunctions[$lower])) {
            return;
        }

        $parts = explode('\\', $lower);
        $last = (string) array_pop($parts);
        if (isset($this->internalFunctions[$last])) {
            return;
        }

        $this->context->report->addIssue(new CompileIssue(
            type: CompileIssue::UNSUPPORTED_FUNCTION,
            symbol: $lower,
            file: $this->context->currentFile(),
            line: $node->getStartLine(),
            reason: 'unknown function — không có trong mapping, không phải PHP internal',
            fallback: 'shim',
        ));
    }

    private function buildReplacement(Expr\FuncCall $node, FunctionMapping $mapping): Node
    {
        if ($mapping->throws) {
            return new Expr\Throw_(
                new Expr\New_(new Name\FullyQualified($mapping->target), $node->args),
            );
        }

        if (!str_contains($mapping->target, '::')) {
            // Native PHP function (vd: wp_json_encode → \json_encode).
            return new Expr\FuncCall(
                new Name\FullyQualified($mapping->target),
                $node->args,
            );
        }

        [$class, $method] = explode('::', $mapping->target, 2);

        $expr = new Expr\StaticCall(new Name\FullyQualified($class), $method, $node->args);

        if ($mapping->wrap !== null) {
            [$wrapClass, $wrapMethod] = explode('::', $mapping->wrap, 2);
            $inner = new Expr\StaticCall(new Name\FullyQualified($wrapClass), $wrapMethod, $node->args);
            $expr = new Expr\StaticCall(new Name\FullyQualified($class), $method, [new Arg($inner)]);
        }

        if ($mapping->echo) {
            $expr = new Expr\Print_($expr);
        }

        return $expr;
    }
}