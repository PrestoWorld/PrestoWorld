<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

/**
 * In-memory counters, flush rate-limited (≥2s) vào stats.json có LOCK_EX.
 *
 * Hot path chỉ ++ mảng tĩnh (không I/O). Đọc từ dashboard gọi read() —
 * buộc flush nếu còn delta để số liệu không bị thiếu các increment gần nhất.
 * Merge read-modify-write dưới 1 file lock nên mọi worker cộng dồn được.
 */
final class StatsCollector
{
    /** @var array<string, int> */
    private static array $delta = [];

    private static float $lastFlush = 0.0;

    private static float $flushInterval = 2.0;

    public static function increment(string $key, int $by = 1): void
    {
        self::$delta[$key] = (self::$delta[$key] ?? 0) + $by;

        $now = microtime(true);
        if ($now - self::$lastFlush >= self::$flushInterval) {
            self::flush();
        }
    }

    /** @return array<string, int> merged counters (file + delta cục bộ chưa flush) */
    public static function read(): array
    {
        self::flush();

        return self::load();
    }

    public static function flush(): void
    {
        if (self::$delta === []) {
            self::$lastFlush = microtime(true);

            return;
        }

        $file = CachePaths::ensureDir() . '/stats.json';
        $delta = self::$delta;
        self::$delta = [];
        self::$lastFlush = microtime(true);

        $fh = @fopen($file, 'c+');
        if ($fh === false) {
            // Không ghi được — trả delta về để không mất số liệu.
            foreach ($delta as $key => $value) {
                self::$delta[$key] = (self::$delta[$key] ?? 0) + $value;
            }

            return;
        }

        flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $current = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : [];

        foreach ($delta as $key => $value) {
            $current[$key] = (int) ($current[$key] ?? 0) + $value;
        }

        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($current));
        flock($fh, LOCK_UN);
        fclose($fh);
    }

    /** @return array<string, int> */
    private static function load(): array
    {
        $file = CachePaths::file('stats.json');
        if (!is_file($file)) {
            return self::$delta;
        }

        $raw = json_decode((string) @file_get_contents($file), true);
        $fileStats = is_array($raw) ? $raw : [];

        foreach (self::$delta as $key => $value) {
            $fileStats[$key] = (int) ($fileStats[$key] ?? 0) + $value;
        }

        return $fileStats;
    }

    /** Reset cục bộ — chỉ dùng trong test. */
    public static function reset(): void
    {
        self::$delta = [];
        self::$lastFlush = 0.0;
    }
}
