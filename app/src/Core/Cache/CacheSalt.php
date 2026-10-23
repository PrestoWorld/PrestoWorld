<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

/**
 * Salt per namespace — nút "Clear" trên dashboard.
 *
 * Bump salt → mọi worker đọc salt mới trong ≤1s (memo TTL) → cache key
 * in-memory (parse/fragment/hooks) đổi theo → entry cũ trở nên unreachable
 * và bị evict dần theo cap. Không cần đụng vào bộ nhớ của từng worker.
 */
final class CacheSalt
{
    /** @var array<string, array{0: string, 1: float}> ns => [salt, memoAt] */
    private static array $memo = [];

    private static float $ttl = 1.0;

    public static function current(string $ns): string
    {
        $entry = self::$memo[$ns] ?? null;
        $now = microtime(true);

        if ($entry !== null && $now - $entry[1] < self::$ttl) {
            return $entry[0];
        }

        $file = CachePaths::ensureDir('salt') . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $ns);
        $salt = is_file($file) ? trim((string) file_get_contents($file)) : '';

        if ($salt === '') {
            $salt = bin2hex(random_bytes(8));
            @file_put_contents($file, $salt, LOCK_EX);
        }

        self::$memo[$ns] = [$salt, $now];

        return $salt;
    }

    public static function bump(string $ns): void
    {
        $file = CachePaths::ensureDir('salt') . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $ns);
        @file_put_contents($file, bin2hex(random_bytes(16)), LOCK_EX);

        // Worker đang gọi bump đọc lại từ file ngay lần kế (memo đã xoá).
        unset(self::$memo[$ns]);
    }
}
