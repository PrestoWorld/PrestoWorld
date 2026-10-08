<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler;

/**
 * Ký hiệu class/interface/trait/enum do plugin khai báo.
 */
final class ClassSymbol
{
    public function __construct(
        public readonly string $name,
        public readonly string $file,
        public readonly int $line,
        public readonly string $kind = 'class',
        public readonly ?string $namespace = null,
    ) {
    }
}
