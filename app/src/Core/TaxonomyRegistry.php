<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * TaxonomyRegistry — register_taxonomy/get_taxonomies (spec 10 §10.4).
 */
final class TaxonomyRegistry
{
    /** @var array<string, array<string, mixed>> */
    private static array $taxonomies = [];

    private function __construct()
    {
    }

    public static function register(string $taxonomy, array $objectTypes = [], array $args = []): void
    {
        $defaults = [
            'label' => ucfirst($taxonomy),
            'public' => true,
            'hierarchical' => false,
            'rewrite' => true,
        ];

        self::$taxonomies[$taxonomy] = array_merge($defaults, $args, ['object_types' => $objectTypes]);
    }

    public static function get(string $taxonomy): ?array
    {
        return self::$taxonomies[$taxonomy] ?? null;
    }

    public static function has(string $taxonomy): bool
    {
        return isset(self::$taxonomies[$taxonomy]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$taxonomies;
    }

    /**
     * @return list<string>
     */
    public static function forObjectType(string $objectType): array
    {
        $result = [];
        foreach (self::$taxonomies as $taxonomy => $config) {
            if (in_array($objectType, (array) ($config['object_types'] ?? []), true)) {
                $result[] = $taxonomy;
            }
        }

        return $result;
    }

    public static function reset(): void
    {
        self::$taxonomies = [];
    }
}