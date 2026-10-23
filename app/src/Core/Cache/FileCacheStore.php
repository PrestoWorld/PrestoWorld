<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

/**
 * File store cho full-page responses: storage/framework/cache/pw/page/{hash}.json
 *
 * Entry: {status, headers, body, created, expires, gen}
 * Gen mismatch → entry đã bị invalidate (content save giữa chừng) → coi như miss.
 */
final class FileCacheStore
{
    public const NS = 'page';

    /** @return array{status: int, headers: array<string, string>, body: string, created: int, expires: int, gen: string}|null */
    public static function get(string $key): ?array
    {
        $file = CachePaths::ensureDir(self::NS) . '/' . $key . '.json';
        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $entry = json_decode($raw, true);
        if (!is_array($entry) || !isset($entry['body'], $entry['expires'], $entry['gen'])) {
            return null;
        }

        if ((int) $entry['expires'] < time()) {
            @unlink($file);

            return null;
        }

        if ($entry['gen'] !== CacheGen::current(self::NS)) {
            @unlink($file);

            return null;
        }

        return [
            'status' => (int) ($entry['status'] ?? 200),
            'headers' => is_array($entry['headers'] ?? null) ? $entry['headers'] : [],
            'body' => (string) $entry['body'],
            'created' => (int) ($entry['created'] ?? 0),
            'expires' => (int) $entry['expires'],
            'gen' => (string) $entry['gen'],
        ];
    }

    /** @param array<string, string> $headers */
    public static function put(string $key, int $status, array $headers, string $body, int $ttl, string $gen): bool
    {
        if ($ttl <= 0) {
            return false;
        }

        $entry = [
            'status' => $status,
            'headers' => $headers,
            'body' => $body,
            'created' => time(),
            'expires' => time() + $ttl,
            'gen' => $gen,
        ];

        $file = CachePaths::ensureDir(self::NS) . '/' . $key . '.json';

        return @file_put_contents($file, json_encode($entry), LOCK_EX) !== false;
    }

    /** @return array{entries: int, bytes: int} */
    public static function stats(): array
    {
        $dir = CachePaths::ensureDir(self::NS);
        $entries = 0;
        $bytes = 0;

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $entries++;
            $size = @filesize($file);
            if ($size !== false) {
                $bytes += $size;
            }
        }

        return ['entries' => $entries, 'bytes' => $bytes];
    }

    public static function flush(): int
    {
        $count = 0;
        foreach (glob(CachePaths::ensureDir(self::NS) . '/*.json') ?: [] as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }

        return $count;
    }
}
