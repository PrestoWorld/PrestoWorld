<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Taxonomy;

/**
 * Taxonomy — WP_Taxonomy replacement (spec 10 §10.5.6).
 */
final class Taxonomy
{
    public string $name = '';

    public string $label = '';

    /** @var array<string, mixed> */
    public array $labels = [];

    public string $description = '';

    public bool $public = true;

    public bool $hierarchical = false;

    public bool $showUi = true;

    public bool $showInRest = false;

    public string $restBase = '';

    public string|bool $queryVar = false;

    /** @var array<string, mixed>|bool */
    public array|bool $rewrite = true;

    /** @var list<string> */
    public array $objectType = [];

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
        if (isset($args['description']) && is_scalar($args['description'])) {
            $this->description = (string) $args['description'];
        }
        if (isset($args['public']) && is_scalar($args['public'])) {
            $this->public = (bool) $args['public'];
        }
        if (isset($args['hierarchical']) && is_scalar($args['hierarchical'])) {
            $this->hierarchical = (bool) $args['hierarchical'];
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
        if (isset($args['query_var'])) {
            $queryVar = $args['query_var'];
            if (is_bool($queryVar) || is_string($queryVar)) {
                $this->queryVar = $queryVar;
            }
        }
        if (isset($args['rewrite'])) {
            $rewrite = $args['rewrite'];
            if (is_bool($rewrite)) {
                $this->rewrite = $rewrite;
            } elseif (is_array($rewrite)) {
                $this->rewrite = self::stringKeys($rewrite);
            }
        }
        if (isset($args['object_type'])) {
            $objectType = $args['object_type'];
            if (is_string($objectType)) {
                $this->objectType = [$objectType];
            } elseif (is_array($objectType)) {
                foreach ($objectType as $type) {
                    if (is_string($type)) {
                        $this->objectType[] = $type;
                    }
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

    public function hasObjectType(string $objectType): bool
    {
        return in_array($objectType, $this->objectType, true);
    }

    public function addObjectType(string $objectType): void
    {
        if (!$this->hasObjectType($objectType)) {
            $this->objectType[] = $objectType;
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'labels' => $this->labels,
            'description' => $this->description,
            'public' => $this->public,
            'hierarchical' => $this->hierarchical,
            'show_ui' => $this->showUi,
            'show_in_rest' => $this->showInRest,
            'rest_base' => $this->restBase,
            'query_var' => $this->queryVar,
            'rewrite' => $this->rewrite,
            'object_type' => $this->objectType,
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
