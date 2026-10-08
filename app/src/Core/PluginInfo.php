<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * PluginInfo — plugin_basename/plugin_dir_url/plugin_dir_path (spec 10 §10.4).
 */
final class PluginInfo
{
    private function __construct()
    {
    }

    public static function basename(string $file): string
    {
        $file = str_replace('\\', '/', (string) $file);
        $pluginsDir = rtrim(SiteInfo::pluginsDir(), '/');
        if ($pluginsDir !== '' && str_starts_with($file, $pluginsDir . '/')) {
            return ltrim(substr($file, strlen($pluginsDir) + 1), '/');
        }

        $parts = explode('/', $file);
        $count = count($parts);

        return implode('/', array_slice($parts, max(0, $count - 2)));
    }

    public static function path(string $file): string
    {
        $basename = self::basename($file);
        $pluginsDir = rtrim(SiteInfo::pluginsDir(), '/');

        return $pluginsDir !== '' ? $pluginsDir . '/' . $basename : $file;
    }

    public static function url(string $file): string
    {
        $basename = self::basename($file);
        $dir = dirname($basename);
        $pluginsUrl = rtrim(SiteInfo::pluginsUrl(), '/');

        return $dir !== '.' && $dir !== '' ? $pluginsUrl . '/' . $dir : $pluginsUrl. '/';
    }

    public static function isActive(string $file): bool
    {
        return \PrestoWorld\Core\PluginState::isActive(self::basename($file));
    }
}