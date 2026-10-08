<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler\Mapping;

use PrestoWorld\Core\Compiler\CompilerException;

/**
 * Mapping cho một WP function → PW service (spec 10 §10.4.1).
 *
 * Mode codes:
 *  - s  : shim only (giữ call, runtime shim xử lý)
 *  - r  : rewrite only (compile-time bắt buộc chuyển)
 *  - sr : cả shim lẫn rewrite
 *  - n  : noop (PW không cần hành vi này)
 *  - x  : unsupported (ghi issue, giữ call fallback)
 */
final class FunctionMapping
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

    public function __construct(
        public readonly string $source,
        public readonly string $target,
        public readonly string $mode,
        public readonly string $group = '',
        public readonly ?string $wrap = null,
        public readonly bool $echo = false,
        public readonly bool $throws = false,
        public readonly ?string $notes = null,
    ) {
        if (!in_array($mode, self::VALID_MODES, true)) {
            throw new CompilerException("Invalid mapping mode \"{$mode}\" for function \"{$source}\"");
        }

        if ($this->supportsRewrite() && $target === '') {
            throw new CompilerException("Rewrite-mode function \"{$source}\" requires a target");
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

    public function isNoop(): bool
    {
        return $this->mode === self::MODE_NOOP;
    }

    public function isUnsupported(): bool
    {
        return $this->mode === self::MODE_UNSUPPORTED;
    }
}
