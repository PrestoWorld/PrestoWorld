<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Filesystem — wp_mkdir_p/wp_tempnam/wp_is_writable... (spec 10 §10.4 Group filesystem).
 */
final class Filesystem
{
    private function __construct()
    {
    }

    public static function mkdirP(string $path, int $permissions = 0755): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return @mkdir($path, $permissions, true);
    }

    public static function tempName(string $dir = '', string $prefix = 'pwtmp'): string
    {
        if ($dir === '') {
            $dir = sys_get_temp_dir();
        }

        self::mkdirP($dir);

        return tempnam($dir, $prefix) ?: $dir . '/' . uniqid($prefix, true);
    }

    public static function copyDir(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        self::mkdirP($destination);

        $items = scandir($source);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $from = $source . '/' . $item;
            $to = $destination . '/' . $item;

            if (is_dir($from)) {
                if (!self::copyDir($from, $to)) {
                    return false;
                }
            } elseif (!@copy($from, $to)) {
                return false;
            }
        }

        return true;
    }

    public static function moveDir(string $source, string $destination): bool
    {
        if (!self::copyDir($source, $destination)) {
            return false;
        }

        return self::deleteDir($source);
    }

    public static function deleteDir(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $items = scandir($path);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $target = $path . '/' . $item;
            if (is_dir($target)) {
                if (!self::deleteDir($target)) {
                    return false;
                }
            } elseif (!@unlink($target)) {
                return false;
            }
        }

        return @rmdir($path);
    }

    public static function isWritable(string $path): bool
    {
        if (function_exists('is_writable')) {
            return @is_writable($path);
        }

        return false;
    }

    public static function modAllowed(string $file, int $perms = 0644): bool
    {
        return is_file($file) || self::mkdirP(dirname($file));
    }

    public static function validate(string $path): bool
    {
        return !str_contains($path, '..') && $path !== '';
    }
}