<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

/**
 * Settings per cache type, persist tại storage/framework/cache/pw/settings.json.
 *
 * Memo 1s/process — thay đổi từ dashboard lan toả tới mọi worker trong ≤1s.
 */
final class CacheSettings
{
    public const TYPES = ['page', 'fragment', 'parse', 'hooks'];

    /** @var array<string, array{enabled: bool, ttl: int}> */
    private static array $cache = [];

    private static float $cachedAt = 0.0;

    /** @return array{enabled: bool, ttl: int} */
    public static function get(string $type): array
    {
        $all = self::all();

        return $all[$type] ?? self::defaults()[$type] ?? ['enabled' => false, 'ttl' => 0];
    }

    public static function enabled(string $type): bool
    {
        return self::get($type)['enabled'];
    }

    public static function ttl(string $type): int
    {
        return self::get($type)['ttl'];
    }

    /** @return array<string, array{enabled: bool, ttl: int}> */
    public static function all(): array
    {
        $now = microtime(true);
        if (self::$cache !== [] && $now - self::$cachedAt < 1.0) {
            return self::$cache;
        }

        $file = CachePaths::file('settings.json');
        $data = [];

        if (is_file($file)) {
            $raw = json_decode((string) @file_get_contents($file), true);
            if (is_array($raw)) {
                foreach ($raw as $type => $entry) {
                    if (!is_string($type) || !is_array($entry)) {
                        continue;
                    }
                    $data[$type] = [
                        'enabled' => (bool) ($entry['enabled'] ?? false),
                        'ttl' => (int) ($entry['ttl'] ?? 0),
                    ];
                }
            }
        }

        foreach (self::defaults() as $type => $default) {
            $data[$type] = array_merge($default, $data[$type] ?? []);
        }

        self::$cache = $data;
        self::$cachedAt = $now;

        return self::$cache;
    }

    public static function set(string $type, bool $enabled, ?int $ttl = null): void
    {
        $all = self::all();
        $current = $all[$type] ?? self::defaults()[$type] ?? ['enabled' => false, 'ttl' => 0];

        $all[$type] = [
            'enabled' => $enabled,
            'ttl' => $ttl ?? $current['ttl'],
        ];

        $dir = CachePaths::ensureDir();
        @file_put_contents($dir . '/settings.json', json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

        self::$cache = $all;
        self::$cachedAt = microtime(true);
    }

    /** @return array<string, array{enabled: bool, ttl: int}> */
    public static function defaults(): array
    {
        return [
            'page' => ['enabled' => true, 'ttl' => 600],
            'fragment' => ['enabled' => true, 'ttl' => 3600],
            'parse' => ['enabled' => true, 'ttl' => 0],
            'hooks' => ['enabled' => true, 'ttl' => 0],
        ];
    }
}
