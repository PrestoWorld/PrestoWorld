<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

/**
 * Central storage layout cho mọi cache do ứng dụng quản lý.
 *
 *   storage/framework/cache/pw/
 *     settings.json        — enabled/ttl per cache type
 *     stats.json           — counters merge cross-worker
 *     timing.json          — render timing aggregates
 *     salt/{ns}            — salt file (manual clear, cross-worker ≤1s)
 *     gen/{ns}             — generation (invalidation on content save)
 *     page/*.json          — full-page responses
 *     fragment/            — (reserved: fragment hiện lưu in-memory per worker)
 */
final class CachePaths
{
    private static ?string $dir = null;

    public static function baseDir(): string
    {
        if (self::$dir !== null) {
            return self::$dir;
        }

        try {
            if (function_exists('storage_path')) {
                self::$dir = rtrim(storage_path('framework/cache/pw'), '/');
            }
        } catch (\Throwable) {
            // Container chưa boot (CLI/worker sớm) — rơi xuống fallback.
        }

        if (self::$dir === null) {
            $base = defined('PW_BASE_PATH') ? PW_BASE_PATH : (string) getcwd();
            self::$dir = rtrim($base, '/') . '/storage/framework/cache/pw';
        }

        return self::$dir;
    }

    public static function file(string $relative): string
    {
        return self::baseDir() . '/' . ltrim($relative, '/');
    }

    public static function ensureDir(string $relative = ''): string
    {
        $dir = $relative === '' ? self::baseDir() : self::baseDir() . '/' . ltrim($relative, '/');

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }
}
