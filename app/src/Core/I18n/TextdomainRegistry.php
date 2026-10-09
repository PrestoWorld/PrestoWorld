<?php

declare(strict_types=1);

namespace PrestoWorld\Core\I18n;

/**
 * TextdomainRegistry — replaces WP_Textdomain_Registry.
 */
final class TextdomainRegistry
{
    /** @var array<string, string> */
    private static array $paths = [];

    /** @var array<string, bool> */
    private static array $loaded = [];

    private function __construct()
    {
    }

    public static function set(string $domain, string $path): void
    {
        self::$paths[$domain] = $path;
        self::$loaded[$domain] = true;
    }

    public static function get(string $domain): ?string
    {
        return self::$paths[$domain] ?? null;
    }

    public static function has(string $domain): bool
    {
        return isset(self::$paths[$domain]);
    }

    public static function remove(string $domain): void
    {
        unset(self::$paths[$domain], self::$loaded[$domain]);
    }

    public static function is_loaded(string $domain): bool
    {
        return self::$loaded[$domain] ?? false;
    }

    public static function reset(): void
    {
        self::$paths = [];
        self::$loaded = [];
    }
}
