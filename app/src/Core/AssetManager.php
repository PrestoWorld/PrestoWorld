<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * AssetManager — wp_enqueue_script/style/localize... (spec 10 §10.4 Group assets).
 *
 * State per-request (§10.7.2: flush sau khi render header).
 */
final class AssetManager
{
    /** @var array<string, array{src: string, deps: array<int, string>, ver: string|bool|null, footer: bool, registered: bool}> */
    private static array $scripts = [];

    /** @var array<string, array{src: string, deps: array<int, string>, ver: string|bool|null, media: string, registered: bool}> */
    private static array $styles = [];

    /** @var list<string> */
    private static array $queueScripts = [];

    /** @var list<string> */
    private static array $queueStyles = [];

    /** @var array<string, array<string, mixed>> */
    private static array $localized = [];

    /** @var array<string, array{position: string, source: string}> */
    private static array $inlineScripts = [];

    /** @var array<string, string> */
    private static array $inlineStyles = [];

    /** @var array<string, array<string, mixed>> */
    private static array $modules = [];

    private function __construct()
    {
    }

    public static function registerStyle(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, string $media = 'all'): void
    {
        self::$styles[$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver, 'media' => $media, 'registered' => true];
    }

    public static function registerScript(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, bool $inFooter = false): void
    {
        self::$scripts[$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver, 'footer' => $inFooter, 'registered' => true];
    }

    public static function enqueueStyle(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, string $media = 'all'): void
    {
        if (!isset(self::$styles[$handle])) {
            self::registerStyle($handle, $src, $deps, $ver, $media);
        }

        if (!in_array($handle, self::$queueStyles, true)) {
            self::$queueStyles[] = $handle;
        }
    }

    public static function enqueueScript(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, bool $inFooter = false): void
    {
        if (!isset(self::$scripts[$handle])) {
            self::registerScript($handle, $src, $deps, $ver, $inFooter);
        }

        if (!in_array($handle, self::$queueScripts, true)) {
            self::$queueScripts[] = $handle;
        }
    }

    public static function dequeueScript(string $handle): bool
    {
        $had = in_array($handle, self::$queueScripts, true);
        self::$queueScripts = array_values(array_filter(self::$queueScripts, static fn (string $h): bool => $h !== $handle));

        return $had;
    }

    public static function deregisterScript(string $handle): bool
    {
        self::dequeueScript($handle);
        $had = isset(self::$scripts[$handle]);
        unset(self::$scripts[$handle]);

        return $had;
    }

    public static function dequeueStyle(string $handle): bool
    {
        $had = in_array($handle, self::$queueStyles, true);
        self::$queueStyles = array_values(array_filter(self::$queueStyles, static fn (string $h): bool => $h !== $handle));

        return $had;
    }

    public static function deregisterStyle(string $handle): bool
    {
        self::dequeueStyle($handle);
        $had = isset(self::$styles[$handle]);
        unset(self::$styles[$handle]);

        return $had;
    }

    public static function scriptIs(string $handle, string $state = 'enqueued'): bool
    {
        return match ($state) {
            'registered' => isset(self::$scripts[$handle]),
            'enqueued' => in_array($handle, self::$queueScripts, true),
            default => false,
        };
    }

    public static function styleIs(string $handle, string $state = 'enqueued'): bool
    {
        return match ($state) {
            'registered' => isset(self::$styles[$handle]),
            'enqueued' => in_array($handle, self::$queueStyles, true),
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function localize(string $handle, string $objectName, array $data): bool
    {
        self::$localized[$handle][$objectName] = $data;

        return true;
    }

    public static function addInlineScript(string $handle, string $data, string $position = 'after', string $dependency = ''): bool
    {
        self::$inlineScripts[$handle] = ['position' => $position, 'source' => $data];

        return true;
    }

    public static function addInlineStyle(string $handle, string $css): bool
    {
        self::$inlineStyles[$handle] = $css;

        return true;
    }

    public static function registerModule(string $name, array $config): void
    {
        self::$modules[$name] = $config;
    }

    /** @return array<string, mixed> */
    public static function module(string $name): array
    {
        return self::$modules[$name] ?? [];
    }

    public static function flush(): void
    {
        self::$scripts = [];
        self::$styles = [];
        self::$queueScripts = [];
        self::$queueStyles = [];
        self::$localized = [];
        self::$inlineScripts = [];
        self::$inlineStyles = [];
        self::$modules = [];
    }
}