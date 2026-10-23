<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

/**
 * Generation per namespace — invalidation tức thì khi nội dung thay đổi.
 *
 * Khác salt (manual clear, memo 1s): gen được đọc lại mỗi lần gọi để mọi
 * request ngay sau khi save nhìn thấy giá trị mới. Page cache đối chiếu gen
 * lúc serve và lúc store; fragment cache đưa gen vào key.
 */
final class CacheGen
{
    public static function current(string $ns): string
    {
        $file = CachePaths::ensureDir('gen') . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $ns);

        if (!is_file($file)) {
            $gen = bin2hex(random_bytes(8));
            @file_put_contents($file, $gen, LOCK_EX);

            return $gen;
        }

        return (string) @file_get_contents($file);
    }

    public static function bump(string $ns): void
    {
        $file = CachePaths::ensureDir('gen') . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $ns);
        @file_put_contents($file, bin2hex(random_bytes(16)), LOCK_EX);
    }
}
