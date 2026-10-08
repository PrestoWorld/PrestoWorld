<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Path — đường dẫn filesystem (tương đương ABSPATH, WP_CONTENT_DIR...).
 */
final class Path
{
    private function __construct()
    {
    }

    public static function home(string $path = ''): string
    {
        $base = Config::string('base_dir', dirname(__DIR__, 4) . '/storage');
        if ($path === '') {
            return rtrim($base, '/') . '/';
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    public static function content(string $path = ''): string
    {
        return self::home('wp-content/' . ltrim($path, '/'));
    }

    public static function plugins(string $path = ''): string
    {
        return self::content('plugins/' . ltrim($path, '/'));
    }

    public static function themes(string $path = ''): string
    {
        return self::content('themes/' . ltrim($path, '/'));
    }

    public static function uploads(string $path = ''): string
    {
        return self::content('uploads/' . ltrim($path, '/'));
    }

    public static function config(): string
    {
        return self::home('config.php');
    }

    public static function randomString(string $type = 'public', int $length = 32): string
    {
        $chars = $type === 'public' ? substr(Config::string('auth_salt'), 0, 40) : Config::string('secure_salt', 'presto');
        if ($chars === '' || $chars === 'presto') {
            $chars = implode('', range('a', 'z')) . implode('', range(0, 9));
        }

        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $out;
    }
}