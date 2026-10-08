<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * PostTypeRegistry — register_post_type/get_post_types (spec 10 §10.4).
 */
final class PostTypeRegistry
{
    /** @var array<string, array<string, mixed>> */
    private static array $types = [];

    private function __construct()
    {
    }

    public static function register(string $postType, array $args = []): void
    {
        $defaults = [
            'label' => ucfirst($postType),
            'public' => true,
            'has_archive' => true,
            'rewrite' => true,
            'supports' => ['title', 'editor'],
        ];

        self::$types[$postType] = array_merge($defaults, $args);
    }

    public static function get(string $postType): ?array
    {
        return self::$types[$postType] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$types;
    }

    public static function exists(string $postType): bool
    {
        return isset(self::$types[$postType]);
    }

    public static function reset(): void
    {
        self::$types = [];
    }
}