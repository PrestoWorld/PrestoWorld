<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler\Mapping;

use PrestoWorld\Core\Compiler\CompilerException;

/**
 * Mapping cho một WP class → PW class (spec 10 §10.5.1).
 *
 * structural mô tả cơ chế chuyển đổi (§10.5.2):
 *  constructor: ioc, static_methods: instance, global_state: scoped
 */
final class ClassMapping
{
    public const MODE_SHIM = 's';
    public const MODE_REWRITE = 'r';
    public const MODE_BOTH = 'sr';
    public const MODE_NOOP = 'n';
    public const MODE_UNSUPPORTED = 'x';

    /** @var list<string> */
    private const VALID_MODES = [
        self::MODE_SHIM,
        self::MODE_REWRITE,
        self::MODE_BOTH,
        self::MODE_NOOP,
        self::MODE_UNSUPPORTED,
    ];

    /**
     * @param array<string, string> $structural
     */
    public function __construct(
        public readonly string $source,
        public readonly string $target,
        public readonly string $mode,
        public readonly array $structural = [],
        public readonly string $group = '',
        public readonly ?string $notes = null,
    ) {
        if (!in_array($mode, self::VALID_MODES, true)) {
            throw new CompilerException("Invalid mapping mode \"{$mode}\" for class \"{$source}\"");
        }

        if ($this->supportsRewrite() && $target === '') {
            throw new CompilerException("Rewrite-mode class \"{$source}\" requires a target");
        }
    }

    public function supportsRewrite(): bool
    {
        return $this->mode === self::MODE_REWRITE || $this->mode === self::MODE_BOTH;
    }

    public function supportsShim(): bool
    {
        return $this->mode === self::MODE_SHIM || $this->mode === self::MODE_BOTH;
    }

    public function isUnsupported(): bool
    {
        return $this->mode === self::MODE_UNSUPPORTED;
    }

    public function isNoop(): bool
    {
        return $this->mode === self::MODE_NOOP;
    }

    public function needsStructuralAdapter(): bool
    {
        return $this->structural !== [];
    }
}
