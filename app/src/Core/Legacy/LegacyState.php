<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Legacy;

use PrestoWorld\Core\Database\PrestoWpdb;

/**
 * Trạng thái legacy theo request (spec 10 §10.7.2 — long-running worker reset).
 *
 * Giữ các singleton core service truy cập được trong mọi ngữ cảnh (có hoặc
 * không có container). reset() xoá toàn bộ state global/static sau mỗi request.
 */
final class LegacyState
{
    private static ?LegacyRegistry $registry = null;
    private static ?LegacyInvoker $invoker = null;
    private static bool $resetRequested = false;

    private function __construct()
    {
    }

    public static function registry(): LegacyRegistry
    {
        if (self::$registry === null) {
            self::$registry = \PrestoWorld\Core\Support\App::make(LegacyRegistry::class, new LegacyRegistry());
        }

        return self::$registry;
    }

    public static function invoker(): LegacyInvoker
    {
        if (self::$invoker === null) {
            self::$invoker = \PrestoWorld\Core\Support\App::make(LegacyInvoker::class, new LegacyInvoker(self::registry()));
        }

        return self::$invoker;
    }

    public static function setRegistry(LegacyRegistry $registry): void
    {
        self::$registry = $registry;
        self::$invoker = null;
    }

    public static function reset(): void
    {
        self::registry()->reset();
        self::invoker()->reset();

        PrestoWpdb::resetInstance();

        \PrestoWorld\Core\Translator::reset();
        \PrestoWorld\Core\CacheRepository::flush();
        \PrestoWorld\Core\AssetManager::flush();

        self::$resetRequested = true;
    }

    /**
     * Reset per-request (lifecycle long-running, spec 10 §10.7.2).
     *
     * KHÔNG xoá registry: hooks đăng ký lúc boot/activation phải sống qua
     * worker (RoadRunner boot 1 lần). Chỉ dọn transient state.
     */
    public static function resetRequest(): void
    {
        self::invoker()->reset();

        self::resetQuery();

        \PrestoWorld\Core\Translator::reset();
        \PrestoWorld\Core\CacheRepository::flush();
        \PrestoWorld\Core\AssetManager::flush();

        self::$resetRequested = true;
    }

    /**
     * Reset CHỈ trạng thái query/post (spec 06 — wp_reset_query/wp_reset_postdata).
     * KHÔNG xoá registry/invoker: hooks đã đăng ký phải sống qua toàn request.
     */
    public static function resetQuery(): void
    {
        $GLOBALS['wp_query'] = new WPQuery();
        if (isset($GLOBALS['post'])) {
            unset($GLOBALS['post']);
        }
    }

    /**
     * Khởi tạo global WordPress (spec 06 §6.2.2): $wpdb, $wp_query.
     * Gọi khi nạp shim (boot + sandbox plugin).
     */
    public static function initGlobals(): void
    {
        if (!isset($GLOBALS['wpdb'])) {
            $GLOBALS['wpdb'] = PrestoWpdb::instance();
        }

        if (!($GLOBALS['wp_query'] ?? null) instanceof WPQuery) {
            $GLOBALS['wp_query'] = new WPQuery();
        }
    }

    public static function wasReset(): bool
    {
        return self::$resetRequested;
    }
}