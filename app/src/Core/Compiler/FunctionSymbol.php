<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler;

/**
 * Ký hiệu hàm do plugin/theme khai báo (Phase A — §10.2.1 Stage 3).
 */
final class FunctionSymbol
{
    public bool $usesExtract = false;
    public bool $usesCompact = false;

    public function __construct(
        public readonly string $name,
        public readonly string $file,
        public readonly int $line,
        public readonly bool $isNested,
        public readonly bool $isConditional,
        public readonly bool $hasFunctionExistsGuard,
        public readonly ?string $namespace = null,
    ) {
    }

    /**
     * Lý do không thể chuyển thành static method (user_fn_skipped), null nếu OK.
     */
    public function skipReason(): ?string
    {
        if ($this->usesExtract) {
            return 'uses extract()';
        }
        if ($this->usesCompact) {
            return 'uses compact()';
        }
        if ($this->isNested) {
            return 'nested function definition';
        }

        return null;
    }

    public function isExtractable(): bool
    {
        if ($this->skipReason() !== null) {
            return false;
        }

        // Conditional chỉ extract được khi có function_exists guard
        // (guard được rewrite thành method_exists — pluggable semantics).
        return !$this->isConditional || $this->hasFunctionExistsGuard;
    }

    /**
     * Còn sót trong output: conditional nhưng không có guard (giữ nguyên
     * semantics định nghĩa có điều kiện) — trừ 3 điểm/hàm (§10.9.2).
     */
    public function isLeftover(): bool
    {
        return $this->skipReason() === null && !$this->isExtractable();
    }

    public function hasPluggableGuard(): bool
    {
        return $this->hasFunctionExistsGuard;
    }
}
