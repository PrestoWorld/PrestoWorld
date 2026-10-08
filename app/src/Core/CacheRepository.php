<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * CacheRepository — wp_cache_* object cache (spec 10 §10.4 + §10.7.2 reset).
 *
 * In-memory per-request, TTL theo $expire giây. Không dùng external cache
 * (usingExternal → false). reset()/flush() giữa request.
 */
final class CacheRepository
{
    /** @var array<string, array{value: mixed, expires: int}> */
    private static array $store = [];

    private static bool $suspended = false;

    private function __construct()
    {
    }

    public static function get(string $key, string $group = '', mixed $default = null): mixed
    {
        if (self::$suspended) {
            return $default;
        }

        $entry = self::$store[self::cacheKey($key, $group)] ?? null;
        if ($entry === null) {
            return $default;
        }

        if ($entry['expires'] !== 0 && $entry['expires'] < time()) {
            unset(self::$store[self::cacheKey($key, $group)]);

            return $default;
        }

        return $entry['value'];
    }

    public static function set(string $key, mixed $value, int $ttl = 0, string $group = ''): bool
    {
        if (self::$suspended) {
            return false;
        }

        self::$store[self::cacheKey($key, $group)] = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ];

        return true;
    }

    public static function has(string $key, string $group = ''): bool
    {
        if (self::$suspended) {
            return false;
        }

        $entry = self::$store[self::cacheKey($key, $group)] ?? null;
        if ($entry === null) {
            return false;
        }

        if ($entry['expires'] !== 0 && $entry['expires'] < time()) {
            unset(self::$store[self::cacheKey($key, $group)]);

            return false;
        }

        return true;
    }

    public static function delete(string $key, string $group = ''): bool
    {
        $id = self::cacheKey($key, $group);
        $had = isset(self::$store[$id]);
        unset(self::$store[$id]);

        return $had;
    }

    public static function flush(): bool
    {
        self::$store = [];

        return true;
    }

    public static function increment(string $key, int $offset = 1, string $group = ''): int|false
    {
        $current = self::get($key, $group, 0);
        if (!is_numeric($current)) {
            $current = 0;
        }

        $value = (int) $current + $offset;
        self::set($key, $value, 0, $group);

        return $value;
    }

    public static function usingExternal(): bool
    {
        return false;
    }

    public static function suspend(bool $suspend = true): void
    {
        self::$suspended = $suspend;
    }

    public static function isSuspended(): bool
    {
        return self::$suspended;
    }

    public static function reset(): void
    {
        self::$store = [];
        self::$suspended = false;
    }

    private static function cacheKey(string $key, string $group): string
    {
        return $group !== '' ? $group . ':' . $key : (string) $key;
    }
}