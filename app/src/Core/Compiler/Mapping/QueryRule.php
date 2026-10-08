<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler\Mapping;

use PrestoWorld\Core\Compiler\CompilerException;

/**
 * Một rule chuyển đổi SQL / wpdb (spec 10 §10.3 — canonical rule set
 * dùng chung cho compile-time Pass 1 lẫn runtime QueryTransformer).
 */
final class QueryRule
{
    public const KIND_SQL = 'sql';
    public const KIND_WPDB = 'wpdb';

    public const TARGET_POSTGRESQL = 'postgresql';
    public const TARGET_SQLITE = 'sqlite';
    public const TARGET_ANY = 'any';

    public const APPLY_COMPILE = 'compile';
    public const APPLY_RUNTIME = 'runtime';
    public const APPLY_BOTH = 'both';

    /** @var list<string> */
    private const VALID_KINDS = [self::KIND_SQL, self::KIND_WPDB];
    /** @var list<string> */
    private const VALID_TARGETS = [self::TARGET_POSTGRESQL, self::TARGET_SQLITE, self::TARGET_ANY];
    /** @var list<string> */
    private const VALID_APPLY = [self::APPLY_COMPILE, self::APPLY_RUNTIME, self::APPLY_BOTH];

    public function __construct(
        public readonly string $kind,
        public readonly string $pattern,
        public readonly string $replacement,
        public readonly string $target,
        public readonly string $apply,
        public readonly string $phase = 'dml',
    ) {
        if (!in_array($kind, self::VALID_KINDS, true)) {
            throw new CompilerException("Invalid query rule kind \"{$kind}\"");
        }

        if (!in_array($target, self::VALID_TARGETS, true)) {
            throw new CompilerException("Invalid query rule target \"{$target}\"");
        }

        if (!in_array($apply, self::VALID_APPLY, true)) {
            throw new CompilerException("Invalid query rule apply \"{$apply}\"");
        }

        if ($kind === self::KIND_SQL && @preg_match($pattern, '') === false) {
            throw new CompilerException("Invalid SQL rule regex \"{$pattern}\"");
        }
    }

    /**
     * Có áp dụng được cho dialect đích không (rule "any" áp dụng cho mọi dialect).
     */
    public function appliesTo(string $dialect): bool
    {
        return $this->target === self::TARGET_ANY || $this->target === $dialect;
    }

    /**
     * Có được dùng trong compile-time Pass 1 không.
     */
    public function appliesAtCompileTime(): bool
    {
        return $this->apply === self::APPLY_COMPILE || $this->apply === self::APPLY_BOTH;
    }

    /**
     * Có được dùng tại runtime (QueryTransformer) không.
     */
    public function appliesAtRuntime(): bool
    {
        return $this->apply === self::APPLY_RUNTIME || $this->apply === self::APPLY_BOTH;
    }
}
