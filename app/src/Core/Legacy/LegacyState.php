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

        if (isset($GLOBALS['wp_query'])) {
            unset($GLOBALS['wp_query']);
        }
        if (isset($GLOBALS['post'])) {
            unset($GLOBALS['post']);
        }

        \PrestoWorld\Core\Translator::reset();
        \PrestoWorld\Core\CacheRepository::flush();
        \PrestoWorld\Core\AssetManager::flush();

        self::$resetRequested = true;
    }

    public static function wasReset(): bool
    {
        return self::$resetRequested;
    }
}