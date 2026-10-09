<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Asset;

/**
 * Replaces WP_Script_Module_Registry — static script module registry.
 */
final class ModuleRegistry
{
    /** @var array<string, array<string, mixed>> */
    private static array $modules = [];

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function register(string $id, array $args = []): bool
    {
        self::$modules[$id] = $args;

        return true;
    }

    public static function unregister(string $id): bool
    {
        $had = isset(self::$modules[$id]);
        unset(self::$modules[$id]);

        return $had;
    }

    public static function isRegistered(string $id): bool
    {
        return isset(self::$modules[$id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $id): ?array
    {
        return self::$modules[$id] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$modules;
    }

    public static function reset(): void
    {
        self::$modules = [];
    }
}
