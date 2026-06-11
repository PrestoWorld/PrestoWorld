<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Pattern;

/**
 * High-performance Memory Storage for RoadRunner
 */
class MemoryStorage implements PatternStorageInterface
{
    protected array $data = [];

    public function get(string $slug): ?string
    {
        return $this->data[$slug] ?? null;
    }

    public function set(string $slug, string $content): void
    {
        $this->data[$slug] = $content;
    }

    public function has(string $slug): bool
    {
        return isset($this->data[$slug]);
    }
}
