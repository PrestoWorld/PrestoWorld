<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Pattern;

class MemoryStorage implements PatternStorageInterface
{
    private const MAX_ENTRIES = 500;

    protected array $data = [];

    public function get(string $slug): ?string
    {
        return $this->data[$slug] ?? null;
    }

    public function set(string $slug, string $content): void
    {
        if (isset($this->data[$slug])) {
            $this->data[$slug] = $content;
            return;
        }

        if (count($this->data) >= self::MAX_ENTRIES) {
            reset($this->data);
            unset($this->data[key($this->data)]);
        }

        $this->data[$slug] = $content;
    }

    public function has(string $slug): bool
    {
        return isset($this->data[$slug]);
    }

    public function clear(): void
    {
        $this->data = [];
    }
}
