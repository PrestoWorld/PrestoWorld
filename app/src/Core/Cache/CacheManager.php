<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

use Witals\Framework\Http\Request;

/**
 * Facade tổng hợp cho dashboard: stats + purge + settings của MỌI loại cache.
 */
final class CacheManager
{
    /** @return array<string, mixed> */
    public static function stats(): array
    {
        $counters = StatsCollector::read();
        $settings = CacheSettings::all();

        $page = FileCacheStore::stats();
        $pageHits = (int) ($counters['page.hit'] ?? 0);
        $pageMisses = (int) ($counters['page.miss'] ?? 0);

        $fragment = FragmentCache::stats();
        $fragHits = (int) ($counters['fragment.hit'] ?? 0);
        $fragMisses = (int) ($counters['fragment.miss'] ?? 0);

        $parseHits = (int) ($counters['parse.hit'] ?? 0);
        $parseMisses = (int) ($counters['parse.miss'] ?? 0);

        $hookHits = (int) ($counters['hooks.hit'] ?? 0);
        $hookMisses = (int) ($counters['hooks.miss'] ?? 0);

        return [
            'types' => [
                'page' => [
                    'label' => 'Full-page cache',
                    'driver' => 'File',
                    'location' => 'storage/framework/cache/pw/page',
                    'description' => 'HTML response đầy đủ (status + headers + body) cho visitor không cookie.',
                    'enabled' => (bool) ($settings['page']['enabled'] ?? false),
                    'ttl' => (int) ($settings['page']['ttl'] ?? 0),
                    'entries' => $page['entries'],
                    'bytes' => $page['bytes'],
                    'hits' => $pageHits,
                    'misses' => $pageMisses,
                    'hit_rate' => self::rate($pageHits, $pageMisses),
                    'clears' => [
                        'Auto: khi lưu template / post / options / styles (generation bump)',
                        'TTL hết hạn (' . (int) ($settings['page']['ttl'] ?? 0) . 's)',
                        'Manual: nút Clear (file xoá + generation bump)',
                    ],
                ],
                'fragment' => [
                    'label' => 'Fragment cache (header/footer)',
                    'driver' => 'RAM',
                    'location' => 'Bộ nhớ worker (per-worker)',
                    'description' => 'HTML đã render của template part — bỏ qua parse + render của part.',
                    'enabled' => (bool) ($settings['fragment']['enabled'] ?? false),
                    'ttl' => (int) ($settings['fragment']['ttl'] ?? 0),
                    'entries' => $fragment['entries'],
                    'bytes' => $fragment['bytes'],
                    'hits' => $fragHits,
                    'misses' => $fragMisses,
                    'hit_rate' => self::rate($fragHits, $fragMisses),
                    'clears' => [
                        'Auto: khi lưu template / post / options (generation bump)',
                        'TTL hết hạn (' . (int) ($settings['fragment']['ttl'] ?? 0) . 's)',
                        'Manual: nút Clear (salt bump → mọi worker miss trong ≤1s)',
                    ],
                ],
                'parse' => [
                    'label' => 'Block parse cache',
                    'driver' => 'RAM',
                    'location' => 'Bộ nhớ worker (per-worker, cap 256)',
                    'description' => 'AST đã parse theo sha1(nội dung) — template + mọi part/pattern nội.',
                    'enabled' => (bool) ($settings['parse']['enabled'] ?? false),
                    'ttl' => null,
                    'entries' => self::parseEntries(),
                    'bytes' => 0,
                    'hits' => $parseHits,
                    'misses' => $parseMisses,
                    'hit_rate' => self::rate($parseHits, $parseMisses),
                    'clears' => [
                        'Auto: nội dung file đổi → sha1 đổi → entry cũ không còn được reference',
                        'Manual: nút Clear (salt bump → mọi worker miss trong ≤1s)',
                    ],
                ],
                'hooks' => [
                    'label' => 'Hooks index (sorted callbacks)',
                    'driver' => 'RAM',
                    'location' => 'Bộ nhớ worker (per-worker)',
                    'description' => 'Danh sách callback đã sort theo priority cho mỗi tag — tránh usort mỗi apply_filters.',
                    'enabled' => (bool) ($settings['hooks']['enabled'] ?? false),
                    'ttl' => null,
                    'entries' => self::hooksEntries(),
                    'bytes' => 0,
                    'hits' => $hookHits,
                    'misses' => $hookMisses,
                    'hit_rate' => self::rate($hookHits, $hookMisses),
                    'clears' => [
                        'Auto: khi add/remove filter/action (registry tự rebuild)',
                        'Manual: nút Clear (salt bump → mọi worker rebuild trong ≤1s)',
                    ],
                ],
            ],
            'framework' => self::frameworkStats(),
            'timing' => RenderTiming::read(),
            'counters' => $counters,
            'generated_at' => date('c'),
        ];
    }

    /**
     * @param string $type page|fragment|parse|hooks|all
     * @return array{type: string, purged: mixed}
     */
    public static function purge(string $type): array
    {
        if ($type === 'all') {
            $purged = [
                'page' => self::purge('page')['purged'],
                'fragment' => self::purge('fragment')['purged'],
                'parse' => self::purge('parse')['purged'],
                'hooks' => self::purge('hooks')['purged'],
            ];

            return ['type' => 'all', 'purged' => $purged];
        }

        return match ($type) {
            'page' => ['type' => $type, 'purged' => PageCache::flush()],
            'fragment' => ['type' => $type, 'purged' => self::purgeFragment()],
            'parse' => ['type' => $type, 'purged' => self::purgeSalted('parse')],
            'hooks' => ['type' => $type, 'purged' => self::purgeSalted('hooks')],
            'framework' => ['type' => $type, 'purged' => self::purgeFramework()],
            default => throw new \InvalidArgumentException("Unknown cache type: $type"),
        };
    }

    public static function setSettings(string $type, bool $enabled, ?int $ttl = null): void
    {
        if (!in_array($type, CacheSettings::TYPES, true)) {
            throw new \InvalidArgumentException("Unknown cache type: $type");
        }

        CacheSettings::set($type, $enabled, $ttl);

        if (!$enabled && $type === 'page') {
            PageCache::flush();
        }

        if (!$enabled && $type === 'fragment') {
            FragmentCache::flush();
        }

        if (!$enabled && in_array($type, ['parse', 'hooks'], true)) {
            CacheSalt::bump($type);
        }
    }

    private static function purgeFragment(): int
    {
        $stats = FragmentCache::stats();
        FragmentCache::flush();

        return (int) $stats['entries'];
    }

    /** Salt bump → mọi worker miss trong ≤1s; trả về entry mẫu của worker này. */
    private static function purgeSalted(string $ns): int
    {
        $entries = 0;

        if ($ns === 'parse' && class_exists(\PrestoWorld\Gutenberg\Parser\BlockSerializer::class)) {
            $entries = (int) (\PrestoWorld\Gutenberg\Parser\BlockSerializer::cacheStats()['entries'] ?? 0);
        } elseif ($ns === 'hooks' && class_exists(\PrestoWorld\Core\Legacy\LegacyState::class)) {
            $entries = (int) (\PrestoWorld\Core\Legacy\LegacyState::registry()->hooksCacheStats()['entries'] ?? 0);
        }

        CacheSalt::bump($ns);

        return $entries;
    }

    /**
     * Xoá các file cache của framework (config/route/rewrite/discovery/views).
     * An toàn: mọi file này đều tự rebuild khi thiếu.
     */
    private static function purgeFramework(): int
    {
        $cacheDir = self::storageDir();
        $count = 0;

        foreach (['config.php', 'routes.php', 'rewrite-rules.json', 'modules-discovery.php', 'plugins-discovery.php'] as $file) {
            $path = $cacheDir . '/' . $file;
            if (is_file($path) && @unlink($path)) {
                $count++;
            }
        }

        foreach (glob($cacheDir . '/views/*.php') ?: [] as $view) {
            if (@unlink($view)) {
                $count++;
            }
        }

        StatsCollector::increment('framework_purged', $count);

        return $count;
    }

    private static function rate(int $hits, int $misses): float
    {
        $total = $hits + $misses;

        return $total > 0 ? round($hits / $total * 100, 1) : 0.0;
    }

    /** Entry count parse cache trong worker hiện tại (mẫu). */
    private static function parseEntries(): int
    {
        if (!class_exists(\PrestoWorld\Gutenberg\Parser\BlockSerializer::class)) {
            return 0;
        }

        return (int) (\PrestoWorld\Gutenberg\Parser\BlockSerializer::cacheStats()['entries'] ?? 0);
    }

    /** Entry count hooks index trong worker hiện tại (mẫu). */
    private static function hooksEntries(): int
    {
        if (!class_exists(\PrestoWorld\Core\Legacy\LegacyState::class)) {
            return 0;
        }

        return (int) (\PrestoWorld\Core\Legacy\LegacyState::registry()->hooksCacheStats()['entries'] ?? 0);
    }

    /** @return list<array{id: string, label: string, entries: int, bytes: int}> */
    private static function frameworkStats(): array
    {
        $cacheDir = self::storageDir();
        $items = [];

        $describe = static function (string $id, string $label, string $file) use ($cacheDir): array {
            $exists = is_file($cacheDir . '/' . $file);
            $bytes = $exists ? (int) (filesize($cacheDir . '/' . $file) ?: 0) : 0;

            return [
                'id' => $id,
                'label' => $label,
                'entries' => $exists ? 1 : 0,
                'bytes' => $bytes,
            ];
        };

        $items[] = $describe('config', 'Config cache', 'config.php');
        $items[] = $describe('routes', 'Route index', 'routes.php');
        $items[] = $describe('rewrite', 'Rewrite rules', 'rewrite-rules.json');
        $items[] = $describe('schema', 'DB schema health', 'db_schema_verified.json');

        foreach (['modules-discovery.php', 'plugins-discovery.php'] as $file) {
            $items[] = $describe('discovery-' . basename($file, '.php'), 'Discovery: ' . $file, $file);
        }

        $views = glob($cacheDir . '/views/*.php') ?: [];
        $items[] = [
            'id' => 'compiled-views',
            'label' => 'Compiled views',
            'entries' => count($views),
            'bytes' => array_sum(array_map(static fn (string $f): int => (int) (filesize($f) ?: 0), $views)),
        ];

        return $items;
    }

    private static function storageDir(): string
    {
        try {
            if (function_exists('storage_path')) {
                return rtrim(storage_path('framework/cache'), '/');
            }
        } catch (\Throwable) {
        }

        $base = defined('PW_BASE_PATH') ? PW_BASE_PATH : (string) getcwd();

        return rtrim($base, '/') . '/storage/framework/cache';
    }
}
