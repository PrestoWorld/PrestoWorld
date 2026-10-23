<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

/**
 * Fragment cache cho template part (header/footer) — in-memory per worker.
 *
 * Vendor (TemplatePartHandler) giữ 2 closure bridge: lookup/store. Ở đây
 * owns policy: enabled?, key = salt+gen+slug+attrs, TTL, cap, local flush.
 *
 *  - gen bump (content save) → key đổi → miss ngay request kế tiếp.
 *  - salt bump (nút Clear dashboard) → key đổi → mọi worker miss ≤1s.
 *  - In-memory: worker recycle (max_jobs) → mất → render lại (vẫn nhanh
 *    nhờ parse cache).
 */
final class FragmentCache
{
    private const CAP = 64;

    /** @var array<string, array{0: string, 1: int}> key => [html, expiresAt] */
    private static array $mem = [];

    public static function lookup(string $slug, array $attributes): ?string
    {
        if (!CacheSettings::enabled('fragment') || $slug === '') {
            return null;
        }

        $key = self::key($slug, $attributes);
        $hit = self::$mem[$key] ?? null;

        if ($hit === null) {
            StatsCollector::increment('fragment.miss');

            return null;
        }

        if ($hit[1] < time()) {
            unset(self::$mem[$key]);
            StatsCollector::increment('fragment.miss');

            return null;
        }

        StatsCollector::increment('fragment.hit');

        return $hit[0];
    }

    public static function store(string $slug, array $attributes, string $html): void
    {
        if (!CacheSettings::enabled('fragment') || $slug === '' || $html === '') {
            return;
        }

        $ttl = CacheSettings::ttl('fragment');
        if ($ttl <= 0) {
            return;
        }

        if (count(self::$mem) >= self::CAP) {
            // FIFO thô — fragment chỉ có vài slug (header/footer/…) nên cap
            // hiếm khi chạm; reset để luôn chứa entry mới nhất.
            self::$mem = [];
        }

        self::$mem[self::key($slug, $attributes)] = [$html, time() + $ttl];
        StatsCollector::increment('fragment.store');
    }

    /** Đếm entry + bytes hiện có trong worker này (mẫu cho dashboard). */
    public static function stats(): array
    {
        $bytes = 0;
        $expiring = 0;
        $now = time();

        foreach (self::$mem as [$html, $expiresAt]) {
            $bytes += strlen($html);
            if ($expiresAt >= $now) {
                $expiring++;
            }
        }

        return ['entries' => $expiring, 'bytes' => $bytes, 'cap' => self::CAP];
    }

    /** Xoá cục bộ worker này + bump salt để các worker khác cũng miss ≤1s. */
    public static function flush(): void
    {
        self::$mem = [];
        CacheSalt::bump('fragment');
    }

    /**
     * Xoá cục bộ KHÔNG bump salt — dùng khi content đổi (gen đã bump,
     * worker khác tự miss qua key; worker này giải phóng bộ nhớ ngay).
     */
    public static function flushLocal(): void
    {
        self::$mem = [];
    }

    private static function key(string $slug, array $attributes): string
    {
        // json_encode attributes — chúng là input cho wrapper() (className,
        // layout, …) nên đổi attr ⇒ HTML div đổi ⇒ không được dùng key cũ.
        $attrs = json_encode($attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return sha1(
            CacheSalt::current('fragment')
            . CacheGen::current('fragment')
            . $slug
            . ($attrs === false ? '' : $attrs)
        );
    }
}
