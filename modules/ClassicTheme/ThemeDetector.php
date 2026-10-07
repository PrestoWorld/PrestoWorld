<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme;

use PrestoWorld\Contracts\Theme\ThemeType;

class ThemeDetector
{
    public static function detect(string $themePath): ThemeType
    {
        $themePath = rtrim($themePath, '/');

        $hasThemeJson = file_exists($themePath . '/theme.json');
        $hasTemplatesDir = is_dir($themePath . '/templates');
        $hasIndexPhp = file_exists($themePath . '/index.php');
        $hasStyleCss = file_exists($themePath . '/style.css');

        if (!$hasStyleCss && !$hasIndexPhp) {
            return ThemeType::BLOCK;
        }

        if ($hasThemeJson && $hasTemplatesDir && !$hasIndexPhp) {
            return ThemeType::BLOCK;
        }

        return ThemeType::CLASSIC;
    }

    public static function isClassic(string $themePath): bool
    {
        return self::detect($themePath) === ThemeType::CLASSIC;
    }

    public static function isBlock(string $themePath): bool
    {
        return self::detect($themePath) === ThemeType::BLOCK;
    }

    public static function resolveActiveThemePath(string $basePath, string $themeName): string
    {
        $paths = [
            $basePath . '/public/wp-content/themes/' . $themeName,
            $basePath . '/content/themes/' . $themeName,
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        return $paths[0];
    }
}
