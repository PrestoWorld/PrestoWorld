<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Clock — thời gian xử lý theo timezone site (spec 10 §10.4).
 */
final class Clock
{
    private function __construct()
    {
    }

    public static function now(): int
    {
        return time();
    }

    /**
     * current_time() — 'timestamp' trả int, ngược lại trả chuỗi Y-m-d H:i:s.
     */
    public static function current(string $type = 'timestamp', bool $gmt = false): string|int
    {
        $ts = time();
        if ($gmt) {
            $ts = gmdate('U', $ts);
        }

        if ($type === 'timestamp') {
            return (int) $ts;
        }

        if ($type === 'mysql') {
            return gmdate('Y-m-d H:i:s', (int) $ts);
        }

        return date($type, (int) $ts);
    }

    public static function date(string|int $timestamp = null, string $format = 'Y-m-d H:i:s', bool $gmt = false): string
    {
        $ts = self::normalizeTimestamp($timestamp ?? time());

        if ($gmt) {
            return gmdate($format, $ts);
        }

        return date($format, $ts);
    }

    public static function timezone(): string
    {
        return Config::string('timezone_string', date_default_timezone_get());
    }

    public static function offset(): float
    {
        return Config::int('gmt_offset', (int) (date('Z') / 3600));
    }

    private static function normalizeTimestamp(string|int $timestamp): int
    {
        if (is_int($timestamp)) {
            return $timestamp;
        }

        if (is_numeric($timestamp)) {
            return (int) $timestamp;
        }

        $parsed = strtotime($timestamp);

        return $parsed === false ? time() : $parsed;
    }
}