<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Asset;

/**
 * Replaces WP_Styles — static registered style registry.
 */
final class StyleRegistry
{
    /** @var array<string, array{src: string, deps: list<string>, ver: string|bool|null, media: string}> */
    private static array $styles = [];

    private function __construct()
    {
    }

    /**
     * @param list<string> $deps
     */
    public static function add(string $handle, string $src = '', array $deps = [], string|bool|null $ver = null, string $media = 'all'): void
    {
        self::$styles[$handle] = [
            'src' => $src,
            'deps' => array_values($deps),
            'ver' => $ver,
            'media' => $media,
        ];
    }

    public static function remove(string $handle): void
    {
        unset(self::$styles[$handle]);
    }

    public static function has(string $handle): bool
    {
        return isset(self::$styles[$handle]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $handle): ?array
    {
        return self::$styles[$handle] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getRegistered(): array
    {
        return self::$styles;
    }

    public static function reset(): void
    {
        self::$styles = [];
    }
}
