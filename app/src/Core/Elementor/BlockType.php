<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Elementor;

/**
 * BlockType — replaces WP_Block_Type (spec 10 §10.5).
 */
class BlockType
{
    public string $name = '';
    public string $title = '';
    public string $category = '';

    /** @var array<string, mixed> */
    public array $attributes = [];

    public mixed $renderCallback = null;

    /** @var array<string, mixed> */
    public array $supports = [];

    public bool $dynamic = false;

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        foreach ($args as $key => $value) {
            if (is_string($key)) {
                $this->set($key, $value);
            }
        }
    }

    public function get(string $key): mixed
    {
        return match ($key) {
            'name' => $this->name,
            'title' => $this->title,
            'category' => $this->category,
            'attributes' => $this->attributes,
            'render_callback', 'renderCallback' => $this->renderCallback,
            'supports' => $this->supports,
            'dynamic' => $this->dynamic,
            default => null,
        };
    }

    public function set(string $key, mixed $value): void
    {
        switch ($key) {
            case 'name':
                $this->name = is_string($value) ? $value : '';
                break;
            case 'title':
                $this->title = is_string($value) ? $value : '';
                break;
            case 'category':
                $this->category = is_string($value) ? $value : '';
                break;
            case 'attributes':
                $this->attributes = is_array($value) ? self::stringKeyed($value) : [];
                break;
            case 'render_callback':
            case 'renderCallback':
                $this->renderCallback = $value;
                break;
            case 'supports':
                $this->supports = is_array($value) ? self::stringKeyed($value) : [];
                break;
            case 'dynamic':
                $this->dynamic = is_bool($value) ? $value : (bool) $value;
                break;
        }
    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'category' => $this->category,
            'attributes' => $this->attributes,
            'render_callback' => $this->renderCallback,
            'supports' => $this->supports,
            'dynamic' => $this->dynamic,
        ];
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
