<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

/**
 * Gọi từ mọi điểm mutation nội dung (save template/post/options/…) —
 * bump generation page + fragment để request kế tiếp không thấy stale.
 *
 * KHÔNG cần đụng parse cache (content-hash tự invalidate) và hooks cache
 * (LegacyRegistry tự clear khi register/remove).
 */
final class CacheInvalidation
{
    public static function onContentChange(string $reason = ''): void
    {
        CacheGen::bump(FileCacheStore::NS);      // page
        CacheGen::bump('fragment');              // fragment (header/footer theo nav/options)

        FragmentCache::flushLocal();
        StatsCollector::increment('invalidations');
    }

    /** Không cần lý do trong hot path — giữ tham số để log mở rộng sau. */
    public static function note(string $reason): void
    {
        StatsCollector::increment('invalidation.' . $reason);
    }
}
