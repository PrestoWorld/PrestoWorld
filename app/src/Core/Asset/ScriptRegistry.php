<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Asset;

/**
 * Replaces WP_Scripts — static registered script registry.
 */
final class ScriptRegistry
{
    /** @var array<string, array{src: string, deps: list<string>, ver: string|bool|null, footer: bool}> */
    private static array $scripts = [];

    private function __construct()
    {
    }

    /**
     * @param list<string> $deps
     */
    public static function add(string $handle, string $src = '', array $deps = [], string|bool|null $ver = null, bool $inFooter = false): void
    {
        self::$scripts[$handle] = [
            'src' => $src,
            'deps' => array_values($deps),
            'ver' => $ver,
            'footer' => $inFooter,
        ];
    }

    public static function remove(string $handle): void
    {
        unset(self::$scripts[$handle]);
    }

    public static function has(string $handle): bool
    {
        return isset(self::$scripts[$handle]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $handle): ?array
    {
        return self::$scripts[$handle] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getRegistered(): array
    {
        return self::$scripts;
    }

    public static function reset(): void
    {
        self::$scripts = [];
    }
}
