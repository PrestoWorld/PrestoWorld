<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Elementor;

/**
 * BlockWrapper — replaces WP_Block (spec 10 §10.5).
 */
class BlockWrapper
{
    /** @var array<string, mixed> */
    private array $block = [];

    /** @param array<string, mixed> $block */
    public function __construct(array $block = [])
    {
        $this->block = $block;
    }

    public function get(string $key): mixed
    {
        return $this->block[$key] ?? null;
    }

    public function name(): string
    {
        $name = $this->block['blockName'] ?? null;

        return is_string($name) ? $name : '';
    }

    public function namePath(): string
    {
        return $this->name();
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        $attributes = $this->block['attrs'] ?? null;

        return is_array($attributes) ? self::stringKeyed($attributes) : [];
    }

    public function content(): string
    {
        $content = $this->block['innerHTML'] ?? null;
        if (is_string($content)) {
            return $content;
        }

        $content = $this->block['innerContent'] ?? null;
        if (is_array($content)) {
            $out = '';
            foreach ($content as $piece) {
                if (is_string($piece)) {
                    $out .= $piece;
                }
            }

            return $out;
        }

        return is_string($content) ? $content : '';
    }

    /**
     * @return array<int, mixed>
     */
    public function innerBlocks(): array
    {
        $inner = $this->block['innerBlocks'] ?? null;

        return is_array($inner) ? array_values($inner) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->block;
    }

    /**
     * @param array<mixed, mixed> $value
     * @return array<string, mixed>
     */
    private static function stringKeyed(array $value): array
    {
        $result = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }
}
