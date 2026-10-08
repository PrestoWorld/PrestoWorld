<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler\Passes;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeVisitor;
use PrestoWorld\Core\Compiler\CompilationContext;
use PrestoWorld\Core\Compiler\CompileIssue;
use PrestoWorld\Core\Compiler\CompileReport;
use PrestoWorld\Core\Compiler\Mapping\MappingRegistry;
use PrestoWorld\Core\Compiler\Mapping\QueryRule;

/**
 * Pass 1 — QueryTransformer (§10.2.1, §10.3).
 *
 * - Rewrite biến $wpdb → PrestoWpdb::instance() (giữ method API; regex SQL
 *   rules chạy ngay tại compile cho string literal).
 * - Bỏ `global $wpdb`.
 * - Áp dụng DDL/DML rules lên SQL string literals (apply=compile|both).
 * - Non-literal SQL (biến truyền vào $wpdb::query-family) → runtime:
 *   ghi issue query_unparsed.
 */
final class QueryPass extends NodeVisitorAbstract implements CompilablePass
{
    private const WPDB_CLASS = 'PrestoWorld\Core\Database\PrestoWpdb';

    private const QUERY_METHODS = ['query', 'get_results', 'get_row', 'get_var', 'get_col', 'prepare'];

    private const SQL_KEYWORDS = [
        'select', 'insert', 'update', 'delete', 'create', 'alter', 'drop',
        'truncate', 'show', 'describe', 'replace', 'grant', 'analyze', 'optimize',
    ];

    private const UNRESOLVED_MYSQL_MARKERS = [
        'AUTO_INCREMENT', '`', 'UNSIGNED', 'ENGINE=', 'DEFAULT CHARSET',
        'ON UPDATE', 'BACKTICK', 'LOCK TABLES', 'UNLOCK TABLES', 'GROUP_CONCAT',
    ];

    public function __construct(private readonly CompilationContext $context)
    {
    }

    /** @var array<int, bool> spl_object_id của Variable $wpdb cần GIỮ NGUYÊN (vị trí write) */
    private array $writeVars = [];

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Expr\Assign || $node instanceof Expr\AssignOp) {
            $this->markWriteTargets($node->var);

            return null;
        }

        if ($node instanceof Stmt\Global_) {
            foreach ($node->vars as $var) {
                if ($var instanceof Expr\Variable && $var->name === 'wpdb') {
                    $this->writeVars[spl_object_id($var)] = true;
                }
            }

            return null;
        }

        if ($node instanceof Stmt\Foreach_) {
            $this->markWriteTargets($node->valueVar);
            if ($node->keyVar !== null) {
                $this->markWriteTargets($node->keyVar);
            }
        }

        return null;
    }

    public function leaveNode(Node $node): Node|int|null
    {
        if ($node instanceof Expr\Variable && $node->name === 'wpdb' && !isset($this->writeVars[spl_object_id($node)])) {
            return new Expr\StaticCall(
                new Name\FullyQualified(self::WPDB_CLASS),
                'instance',
            );
        }

        if ($node instanceof Stmt\Global_) {
            $remaining = [];
            foreach ($node->vars as $var) {
                if (!($var instanceof Expr\Variable && $var->name === 'wpdb')) {
                    $remaining[] = $var;
                }
            }

            return $remaining === [] ? NodeVisitor::REMOVE_NODE : $node;
        }

        if ($node instanceof Scalar\String_) {
            $this->transformSqlString($node);

            return null;
        }

        if ($node instanceof Expr\MethodCall) {
            $this->flagRuntimeSql($node);

            return null;
        }

        return null;
    }

    private function markWriteTargets(Node $expr): void
    {
        $errors = [$expr];

        while ($errors !== []) {
            /** @var Node $current */
            $current = array_pop($errors);

            if ($current instanceof Expr\Variable && $current->name === 'wpdb') {
                $this->writeVars[spl_object_id($current)] = true;
                continue;
            }

            if ($current instanceof Expr\Array_ || $current instanceof Expr\List_) {
                foreach ($current->items as $item) {
                    if ($item instanceof Expr\ArrayItem) {
                        $errors[] = $item->value;
                    }
                }
            }
        }
    }

    private function flagRuntimeSql(Expr\MethodCall $node): void
    {
        $var = $node->var;
        if (!$var instanceof Expr\StaticCall) {
            return;
        }
        if (!$var->class instanceof Name || $var->class->toString() !== self::WPDB_CLASS) {
            return;
        }

        $method = $node->name instanceof Node\Identifier ? strtolower($node->name->toString()) : '';
        if (!in_array($method, self::QUERY_METHODS, true)) {
            return;
        }

        $arg = $node->args[0]->value ?? null;
        if ($arg instanceof Scalar\String_) {
            return;
        }

        $this->context->report->addIssue(new CompileIssue(
            type: CompileIssue::QUERY_UNPARSED,
            symbol: 'wpdb->' . $method,
            file: $this->context->currentFile(),
            line: $node->getStartLine(),
            reason: 'dynamic SQL — chuyển thẳng vào PrestoWpdb::query() runtime transform',
            fallback: 'runtime',
        ));
    }

    private function transformSqlString(Scalar\String_ $node): void
    {
        $value = $node->value;
        if (!$this->looksLikeSql($value)) {
            return;
        }

        $dialect = $this->context->targetDialect;
        $rules = $this->context->mappings->queryRules($dialect, QueryRule::KIND_SQL);

        $transformed = $value;
        $changed = false;

        foreach ($rules as $rule) {
            if (!$rule->appliesAtCompileTime()) {
                continue;
            }
            if (!$this->ruleAppliesToPhase($rule, $value)) {
                continue;
            }

            $replacement = @preg_replace($rule->pattern, $rule->replacement, $transformed);
            if ($replacement === null || $replacement === $transformed) {
                continue;
            }

            $transformed = $replacement;
            $changed = true;
        }

        if ($changed) {
            $node->value = $transformed;
            $this->context->report->incrementStat(CompileReport::STAT_QUERIES_TRANSFORMED);
            $this->warnUnresolvedMarkers($node, $transformed);
        }
    }

    private function ruleAppliesToPhase(QueryRule $rule, string $sql): bool
    {
        $lower = strtolower(ltrim($sql, " \t\n\r\0\x0B("));
        $keyword = strtok($lower, " \t\n\r") ?: '';

        if ($rule->phase === 'ddl') {
            return in_array($keyword, ['create', 'alter', 'drop', 'truncate', 'show', 'describe'], true);
        }

        return in_array($keyword, ['select', 'insert', 'update', 'delete', 'replace', 'set'], true);
    }

    private function looksLikeSql(string $value): bool
    {
        $lower = strtolower(ltrim($value, " \t\n\r\0\x0B("));
        $keyword = strtok($lower, " \t\n\r") ?: '';

        return in_array($keyword, self::SQL_KEYWORDS, true);
    }

    /**
     * SQL vẫn còn dấu hiệu MySQL chưa resolve sau compile rules → cảnh báo.
     */
    private function warnUnresolvedMarkers(Scalar\String_ $node, string $sql): void
    {
        foreach (self::UNRESOLVED_MYSQL_MARKERS as $marker) {
            if ($marker === 'BACKTICK') {
                continue;
            }
            if (str_contains(strtoupper($sql), $marker)) {
                $this->context->report->addIssue(new CompileIssue(
                    type: CompileIssue::UNSAFE_SQL,
                    symbol: 'mysql-marker ' . $marker,
                    file: $this->context->currentFile(),
                    line: $node->getStartLine(),
                    reason: 'SQL vẫn chứa cú pháp MySQL chưa được transform',
                    fallback: 'runtime',
                ));

                return;
            }
        }
    }
}