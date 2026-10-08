<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * AppInfo — môi trường chạy + quản lý memory (wp_environment_type, wp_raise_memory_limit).
 */
final class AppInfo
{
    private function __construct()
    {
    }

    public static function environment(): string
    {
        $env = Config::string('environment', 'production');
        $known = ['local', 'development', 'staging', 'production'];

        return in_array($env, $known, true) ? $env : 'production';
    }

    public static function isDebug(): bool
    {
        return (bool) Config::get('debug', false);
    }

    public static function raiseMemory(string $context = 'core'): int
    {
        $current = self::currentMemoryLimit();
        $desired = max($current, 256 * 1024 * 1024);

        if ($current < $desired) {
            ini_set('memory_limit', (string) $desired);
        }

        return $desired;
    }

    public static function currentMemoryLimit(): int
    {
        $raw = (string) ini_get('memory_limit');
        if ($raw === '' || $raw === '-1') {
            return PHP_INT_MAX;
        }

        $value = (int) $raw;
        if (str_ends_with(strtolower($raw), 'k')) {
            $value *= 1024;
        } elseif (str_ends_with(strtolower($raw), 'm')) {
            $value *= 1024 * 1024;
        } elseif (str_ends_with(strtolower($raw), 'g')) {
            $value *= 1024 * 1024 * 1024;
        }

        return $value;
    }
}