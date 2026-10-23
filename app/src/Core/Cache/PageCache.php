<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Full-page cache cho GET thuần — hoàn toàn minh bạch với end user.
 *
 * Nguyên tắc trong suốt:
 *  - Replay response Y HỆT status + headers + body đã lưu (không thêm header
 *    X-Cache/Age/... nào) → không phân biệt được HIT với MISS.
 *  - Bypass nếu request có BẤT KỲ cookie nào (người login/đã tương tác luôn
 *    thấy content tươi) + bypass path admin/api + non-GET + non-200.
 *  - Strip Set-Cookie trước khi lưu (không bao giờ leak session ra visitor).
 *  - Gen mismatch / TTL hết hạn → miss tự nhiên.
 */
final class PageCache
{
    /** @var list<string> prefix không được cache */
    private const BYPASS_PREFIXES = [
        '/dashboard',
        '/wp-json',
        '/api/',
        '/site-editor',
        '/login',
        '/wp-admin',
        '/setup',
    ];

    public static function eligible(Request $request): bool
    {
        if (!CacheSettings::enabled('page')) {
            return false;
        }

        if (!$request->isMethod('GET')) {
            return false;
        }

        if ($request->cookies() !== []) {
            return false;
        }

        $path = $request->path();
        foreach (self::BYPASS_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/'))) {
                return false;
            }
        }

        return true;
    }

    public static function cacheKey(Request $request): string
    {
        // Accept-Language quyết định locale khi không có cookie/URL prefix
        // (LocaleMiddleware fallback) — đưa vào key để không trộn bản dịch.
        $acceptLanguage = (string) ($request->header('Accept-Language') ?? '');
        $acceptLanguage = substr($acceptLanguage, 0, 100);

        return sha1($request->path() . '?' . $request->uri() . "\n" . $acceptLanguage);
    }

    public static function serve(Request $request, string $gen): ?Response
    {
        if (!self::eligible($request)) {
            return null;
        }

        $entry = FileCacheStore::get(self::cacheKey($request));

        if ($entry === null || $entry['gen'] !== $gen) {
            StatsCollector::increment('page.miss');

            return null;
        }

        StatsCollector::increment('page.hit');

        return new Response($entry['body'], $entry['status'], $entry['headers']);
    }

    public static function store(Request $request, Response $response, string $gen): void
    {
        if (!self::eligible($request)) {
            return;
        }

        if ($response->getStatusCode() !== 200) {
            return;
        }

        $contentType = (string) ($response->getHeader('Content-Type') ?? '');
        if (!str_contains($contentType, 'text/html')) {
            return;
        }

        // Gen đã đổi trong lúc render (save xảy ra giữa chừng) → bỏ, không lưu.
        if (CacheGen::current(FileCacheStore::NS) !== $gen) {
            return;
        }

        $headers = [];
        foreach ($response->getHeaders() as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            // Không bao giờ lưu Set-Cookie — replay sẽ leak session.
            if (strtolower((string) $name) === 'set-cookie') {
                continue;
            }
            $headers[(string) $name] = (string) $value;
        }

        $ttl = CacheSettings::ttl('page');
        if ($ttl <= 0) {
            return;
        }

        if (FileCacheStore::put(self::cacheKey($request), 200, $headers, $response->getContent(), $ttl, $gen)) {
            StatsCollector::increment('page.store');
        }
    }

    /** Xoá toàn bộ page cache (file + reset stat cục bộ không cần). */
    public static function flush(): int
    {
        CacheGen::bump(FileCacheStore::NS);

        return FileCacheStore::flush();
    }
}
