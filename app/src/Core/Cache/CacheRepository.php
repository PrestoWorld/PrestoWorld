<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

/**
 * Replaces WP_Object_Cache — static in-memory object cache with TTL.
 */
final class CacheRepository
{
    /** @var array<string, array{value: mixed, expires: int}> */
    private static array $store = [];

    private function __construct()
    {
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $entry = self::$store[$key] ?? null;
        if ($entry === null) {
            return $default;
        }

        if ($entry['expires'] > 0 && $entry['expires'] < time()) {
            unset(self::$store[$key]);

            return $default;
        }

        return $entry['value'];
    }

    public static function set(string $key, mixed $value, int $ttl = 0): bool
    {
        self::$store[$key] = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ];

        return true;
    }

    public static function add(string $key, mixed $value, int $ttl = 0): bool
    {
        if (self::has($key)) {
            return false;
        }

        return self::set($key, $value, $ttl);
    }

    public static function delete(string $key): bool
    {
        $had = isset(self::$store[$key]);
        unset(self::$store[$key]);

        return $had;
    }

    public static function has(string $key): bool
    {
        $entry = self::$store[$key] ?? null;
        if ($entry === null) {
            return false;
        }

        if ($entry['expires'] > 0 && $entry['expires'] < time()) {
            unset(self::$store[$key]);

            return false;
        }

        return true;
    }

    public static function flush(): void
    {
        self::$store = [];
    }

    public static function incr(string $key, int $offset = 1): int
    {
        $current = self::get($key, 0);
        if (!is_numeric($current)) {
            $current = 0;
        }

        $value = (int) $current + $offset;
        self::set($key, $value);

        return $value;
    }

    public static function decr(string $key, int $offset = 1): int
    {
        return self::incr($key, -$offset);
    }

    public static function reset(): void
    {
        self::$store = [];
    }
}
