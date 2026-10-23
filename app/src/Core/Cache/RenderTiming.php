<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

/**
 * Aggregate render timing theo template (Phase 0 instrumentation).
 *
 * Ghi nhận mọi request (hrtime ~ns), flush rate-limited (≥5s) vào
 * timing.json với merge cộng dồn cross-worker. Dashboard đọc để vẽ bảng
 * load/parse/render per template.
 */
final class RenderTiming
{
    /** @var array<string, array{count: int, load: float, parse: float, render: float, last: int}> */
    private static array $delta = [];

    private static float $lastFlush = 0.0;

    private static float $flushInterval = 5.0;

    public static function record(string $template, float $loadMs, float $parseMs, float $renderMs): void
    {
        $row = self::$delta[$template] ?? ['count' => 0, 'load' => 0.0, 'parse' => 0.0, 'render' => 0.0, 'last' => 0];
        $row['count'] += 1;
        $row['load'] += $loadMs;
        $row['parse'] += $parseMs;
        $row['render'] += $renderMs;
        $row['last'] = time();
        self::$delta[$template] = $row;

        if (microtime(true) - self::$lastFlush >= self::$flushInterval) {
            self::flush();
        }
    }

    /** @return array<string, array{count: int, avg_load: float, avg_parse: float, avg_render: float, last: int}> */
    public static function read(): array
    {
        self::flush();

        $file = CachePaths::file('timing.json');
        $raw = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
        $data = is_array($raw) ? $raw : [];

        $out = [];
        foreach ($data as $template => $row) {
            if (!is_string($template) || !is_array($row) || (int) ($row['count'] ?? 0) <= 0) {
                continue;
            }
            $count = (int) $row['count'];
            $out[$template] = [
                'count' => $count,
                'avg_load' => round((float) $row['load'] / $count, 3),
                'avg_parse' => round((float) $row['parse'] / $count, 3),
                'avg_render' => round((float) $row['render'] / $count, 3),
                'last' => (int) ($row['last'] ?? 0),
            ];
        }

        uasort($out, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return $out;
    }

    public static function flush(): void
    {
        if (self::$delta === []) {
            self::$lastFlush = microtime(true);

            return;
        }

        $file = CachePaths::ensureDir() . '/timing.json';
        $delta = self::$delta;
        self::$delta = [];
        self::$lastFlush = microtime(true);

        $fh = @fopen($file, 'c+');
        if ($fh === false) {
            foreach ($delta as $key => $row) {
                self::$delta[$key] = self::$delta[$key] ?? $row;
            }

            return;
        }

        flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $current = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : [];

        foreach ($delta as $key => $row) {
            $existing = is_array($current[$key] ?? null) ? $current[$key] : [];
            $current[$key] = [
                'count' => (int) ($existing['count'] ?? 0) + $row['count'],
                'load' => (float) ($existing['load'] ?? 0) + $row['load'],
                'parse' => (float) ($existing['parse'] ?? 0) + $row['parse'],
                'render' => (float) ($existing['render'] ?? 0) + $row['render'],
                'last' => max((int) ($existing['last'] ?? 0), $row['last']),
            ];
        }

        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($current));
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}
