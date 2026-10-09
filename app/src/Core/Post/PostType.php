<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Post;

/**
 * PostType — WP_Post_Type replacement (spec 10 §10.5.4).
 */
final class PostType
{
    public string $name = '';

    public string $label = '';

    /** @var array<string, mixed> */
    public array $labels = [];

    public bool $public = true;

    public bool $hierarchical = false;

    public bool $publiclyQueryable = true;

    public bool $showUi = true;

    public bool $showInRest = false;

    public string $restBase = '';

    /** @var list<string> */
    public array $supports = [];

    /** @var list<string> */
    public array $taxonomies = [];

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        if (isset($args['name']) && is_scalar($args['name'])) {
            $this->name = (string) $args['name'];
        }
        if (isset($args['label']) && is_scalar($args['label'])) {
            $this->label = (string) $args['label'];
        }
        if (isset($args['labels']) && is_array($args['labels'])) {
            $this->labels = self::stringKeys($args['labels']);
        }
        if (isset($args['public']) && is_scalar($args['public'])) {
            $this->public = (bool) $args['public'];
        }
        if (isset($args['hierarchical']) && is_scalar($args['hierarchical'])) {
            $this->hierarchical = (bool) $args['hierarchical'];
        }
        if (isset($args['publicly_queryable']) && is_scalar($args['publicly_queryable'])) {
            $this->publiclyQueryable = (bool) $args['publicly_queryable'];
        }
        if (isset($args['show_ui']) && is_scalar($args['show_ui'])) {
            $this->showUi = (bool) $args['show_ui'];
        }
        if (isset($args['show_in_rest']) && is_scalar($args['show_in_rest'])) {
            $this->showInRest = (bool) $args['show_in_rest'];
        }
        if (isset($args['rest_base']) && is_scalar($args['rest_base'])) {
            $this->restBase = (string) $args['rest_base'];
        }
        if (isset($args['supports']) && is_array($args['supports'])) {
            foreach ($args['supports'] as $key => $value) {
                if (is_string($key) && $value) {
                    $this->supports[] = $key;
                } elseif (is_string($value) && $value !== '') {
                    $this->supports[] = $value;
                }
            }
        }
        if (isset($args['taxonomies']) && is_array($args['taxonomies'])) {
            foreach ($args['taxonomies'] as $taxonomy) {
                if (is_string($taxonomy)) {
                    $this->taxonomies[] = $taxonomy;
                }
            }
        }
    }

    public function get(string $key): mixed
    {
        $vars = get_object_vars($this);

        return $vars[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
        }
    }

    public function has(string $feature): bool
    {
        return in_array($feature, $this->supports, true);
    }

    public function addSupport(string ...$features): void
    {
        foreach ($features as $feature) {
            if (!$this->has($feature)) {
                $this->supports[] = $feature;
            }
        }
    }

    public function removeSupport(string $feature): void
    {
        $this->supports = array_values(array_filter(
            $this->supports,
            static fn (string $item): bool => $item !== $feature,
        ));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'labels' => $this->labels,
            'public' => $this->public,
            'hierarchical' => $this->hierarchical,
            'publicly_queryable' => $this->publiclyQueryable,
            'show_ui' => $this->showUi,
            'show_in_rest' => $this->showInRest,
            'rest_base' => $this->restBase,
            'supports' => $this->supports,
            'taxonomies' => $this->taxonomies,
        ];
    }

    /**
     * @param array<int|string, mixed> $input
     * @return array<string, mixed>
     */
    private static function stringKeys(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }
}
