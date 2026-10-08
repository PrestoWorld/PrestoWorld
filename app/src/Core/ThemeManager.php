<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * ThemeManager — get_template_directory/.../get_header/... (spec 10 §10.4 Group theme).
 */
final class ThemeManager
{
    /** @var array<string, array<int, mixed>> */
    private static array $supports = [];

    /** @var list<string> */
    private static array $templateStack = [];

    private function __construct()
    {
    }

    public static function directory(): string
    {
        return rtrim(SiteInfo::themeDir() !== '' ? SiteInfo::themeDir() : Path::themes(), '/') . '/' . Config::string('template', 'presto');
    }

    public static function stylesheetDirectory(): string
    {
        return rtrim(SiteInfo::themeDir() !== '' ? SiteInfo::themeDir() : Path::themes(), '/') . '/' . Config::string('stylesheet', 'presto');
    }

    public static function directoryUri(): string
    {
        return self::themeRootUri(Config::string('template', 'presto'));
    }

    public static function stylesheetUri(): string
    {
        return self::themeRootUri(Config::string('stylesheet', 'presto'));
    }

    public static function stylesheet(): string
    {
        return Config::string('stylesheet', 'presto');
    }

    public static function template(): string
    {
        return Config::string('template', 'presto');
    }

    public static function templateUri(): string
    {
        return self::directoryUri();
    }

    public static function get(string $stylesheet = ''): Theme\ThemeEntity
    {
        $name = $stylesheet !== '' ? $stylesheet : self::stylesheet();

        return new Theme\ThemeEntity(
            name: $name,
            stylesheet: $name,
            template: $name,
            directory: self::directory(),
            uri: self::directoryUri(),
        );
    }

    public static function locate(string $template): ?string
    {
        $file = self::stylesheetDirectory() . '/' . ltrim($template, '/');
        if (is_file($file)) {
            return $file;
        }

        $fallback = self::directory() . '/' . ltrim($template, '/');
        if (is_file($fallback)) {
            return $fallback;
        }

        return null;
    }

    public static function load(string $template): void
    {
        $file = self::locate($template);
        if ($file !== null) {
            self::$templateStack[] = $file;
            require $file;
            array_pop(self::$templateStack);
        }
    }

    public static function header(string $name = 'header'): void
    {
        self::load('header.php');
    }

    public static function footer(string $name = 'footer'): void
    {
        self::load('footer.php');
    }

    public static function sidebar(string $name = 'sidebar'): void
    {
        self::load('sidebar.php');
    }

    public static function templatePart(string $slug, string $name = ''): void
    {
        self::load($slug . ($name !== '' ? '-' . $name : '') . '.php');
    }

    public static function addSupport(string $feature, mixed $args = []): void
    {
        self::$supports[$feature] = $args;
    }

    public static function removeSupport(string $feature): bool
    {
        $had = isset(self::$supports[$feature]);
        unset(self::$supports[$feature]);

        return $had;
    }

    public static function supports(string $feature): bool
    {
        return isset(self::$supports[$feature]);
    }

    public static function getSupport(string $feature): mixed
    {
        return self::$supports[$feature] ?? null;
    }

    public static function reset(): void
    {
        self::$supports = [];
        self::$templateStack = [];
    }

    private static function themeRootUri(string $theme): string
    {
        return rtrim(SiteInfo::themeUrl() !== '' ? SiteInfo::themeUrl() : Url::content('themes'), '/') . '/' . $theme;
    }
}