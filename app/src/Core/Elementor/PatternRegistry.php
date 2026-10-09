<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Elementor;

/**
 * PatternRegistry — replaces WP_Block_Patterns_Registry (spec 10 §10.5).
 */
final class PatternRegistry
{
    /** @var array<string, array<string, mixed>> */
    private static array $patterns = [];

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $properties
     */
    public static function register(string $name, array $properties = []): bool
    {
        self::$patterns[$name] = $properties;

        return true;
    }

    public static function unregister(string $name): bool
    {
        $had = isset(self::$patterns[$name]);
        unset(self::$patterns[$name]);

        return $had;
    }

    public static function isRegistered(string $name): bool
    {
        return isset(self::$patterns[$name]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $name): ?array
    {
        return self::$patterns[$name] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$patterns;
    }

    public static function reset(): void
    {
        self::$patterns = [];
    }
}
