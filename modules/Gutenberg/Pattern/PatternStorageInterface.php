<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Pattern;

/**
 * Strategy for pattern persistence
 */
interface PatternStorageInterface
{
    public function get(string $slug): ?string;
    public function set(string $slug, string $content): void;
    public function has(string $slug): bool;
}
