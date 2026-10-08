<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Format — tương đương number_format_i18n, human_time_diff, trailingslashit,
 * wp_autop... (spec 10 §10.4 Groups).
 */
final class Format
{
    private function __construct()
    {
    }

    public static function number(float $number, int $decimals = 0): string
    {
        return number_format($number, $decimals, '.', ',');
    }

    public static function date(string|int $timestamp, string $format = 'Y-m-d H:i:s'): string
    {
        $ts = self::normalizeTimestamp($timestamp);

        return date($format, $ts);
    }

    public static function mysqlDate(string|int $timestamp = null): string
    {
        return date('Y-m-d H:i:s', self::normalizeTimestamp($timestamp ?? time()));
    }

    public static function humanTimeDiff(string|int $from, string|int $to = null): string
    {
        $from = self::normalizeTimestamp($from);
        $to = $to === null ? time() : self::normalizeTimestamp($to);

        $diff = abs($to - $from);

        $units = [
            [YEAR_IN_SECONDS, 'year', 'years'],
            [MONTH_IN_SECONDS, 'month', 'months'],
            [WEEK_IN_SECONDS, 'week', 'weeks'],
            [DAY_IN_SECONDS, 'day', 'days'],
            [HOUR_IN_SECONDS, 'hour', 'hours'],
            [MINUTE_IN_SECONDS, 'minute', 'minutes'],
        ];

        foreach ($units as [$seconds, $singular, $plural]) {
            $count = (int) floor($diff / $seconds);
            if ($count >= 1) {
                return sprintf('%d %s', $count, $count === 1 ? $singular : $plural);
            }
        }

        return $diff <= 1 ? '1 second' : sprintf('%d seconds', $diff);
    }

    public static function toGmt(string|int $timestamp): string
    {
        return gmdate('Y-m-d H:i:s', self::normalizeTimestamp($timestamp));
    }

    public static function fromGmt(string|int $timestamp): string
    {
        return date('Y-m-d H:i:s', self::normalizeTimestamp($timestamp));
    }

    /**
     * size_format.
     */
    public static function size(int|float $bytes, int $decimals = 0): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = 0;

        $bytes = (float) $bytes;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return number_format($bytes, $decimals) . ' ' . $units[$i];
    }

    /**
     * wp_html_excerpt — strip tags + cut theo từ.
     */
    public static function htmlExcerpt(string $text, int $length = 55, string $more = '&hellip;'): string
    {
        return self::trimWords(Sanitize::stripTags($text, true), $length, $more);
    }

    public static function trimWords(string $text, int $numWords = 55, string $more = '&hellip;'): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $words = preg_split('/\s+/', $text) ?: [];

        if (count($words) <= $numWords) {
            return $text;
        }

        return implode(' ', array_slice($words, 0, $numWords)) . ' ' . $more;
    }

    public static function trimExcerpt(string $text, int $length = 55): string
    {
        return self::trimWords($text, $length, '&hellip;');
    }

    public static function trailingslash(string $value): string
    {
        return rtrim($value, '/\\') . '/';
    }

    public static function untrailingslash(string $value): string
    {
        return rtrim($value, '/\\');
    }

    public static function basename(string $path, string $suffix = ''): string
    {
        $name = basename($path);
        if ($suffix !== '' && str_ends_with($name, $suffix)) {
            $name = substr($name, 0, -strlen($suffix));
        }

        return $name;
    }

    public static function normalizePath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = [];
        foreach (explode(DIRECTORY_SEPARATOR, $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        $prefix = str_starts_with($path, DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '';
        if (preg_match('/^[A-Za-z]:/', $path) === 1) {
            $prefix = substr($path, 0, 3);
        }

        return $prefix . implode(DIRECTORY_SEPARATOR, $parts);
    }

    public static function pathJoin(string $base, string ...$paths): string
    {
        $joined = self::untrailingslash($base);
        foreach ($paths as $path) {
            $joined .= DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        }

        return self::normalizePath($joined);
    }

    public static function serialize(mixed $data): string
    {
        return is_string($data) ? maybe_serialize_fallback($data) : serialize($data);
    }

    public static function unserialize(string $data): mixed
    {
        if (!self::isSerialized($data)) {
            return $data;
        }

        $result = @unserialize($data);
        if ($result === false && $data !== 'b:0;') {
            return $data;
        }

        return $result;
    }

    public static function isSerialized(string $data): bool
    {
        if ($data === '') {
            return false;
        }

        $data = trim($data);
        if ($data === 'N;') {
            return true;
        }

        $token = $data[0];
        if (!in_array($token, ['a', 'O', 's', 'i', 'd', 'b'], true)) {
            return false;
        }

        return (bool) preg_match('/^[aOsidb]:.*;?$/s', $data);
    }

    /**
     * wptexturize (subset — dấu quotes typographic đơn giản).
     */
    public static function texturize(string $text): string
    {
        $text = str_replace(['"', "'"], ['&#8220;', '&#8216;'], $text);
        $text = (string) preg_replace('/--/', '&#8212;', $text) ?? $text;

        return (string) preg_replace('/\.\.\./', '&#8230;', $text) ?? $text;
    }

    /**
     * wpautop (subset — paragraph basic).
     */
    public static function autop(string $text): string
    {
        $text = trim((string) preg_replace('/[\r\n]+/', "\n\n", $text));
        $blocks = preg_split('/\n\n+/', $text) ?: [];

        $result = '';
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }
            if (!preg_match('/^<(\/)?(p|div|ul|ol|li|table|h[1-6]|blockquote|pre|form)/', $block)) {
                $block = '<p>' . $block . '</p>';
            }
            $result .= $block . "\n";
        }

        return $result;
    }

    public static function smilies(string $text): string
    {
        $map = [':)' => '🙂', ':(' => '🙁', ':D' => '😀', ';)' => '😉', ':P' => '😛', '8)' => '😎'];

        return str_replace(array_keys($map), array_values($map), $text);
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

if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
}
if (!defined('MONTH_IN_SECONDS')) {
    define('MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS);
}
if (!defined('YEAR_IN_SECONDS')) {
    define('YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS);
}

if (!function_exists('maybe_serialize_fallback')) {
    /**
     * Chuỗi đã là serialize string (có marker) thì giữ nguyên, ngược lại serialize.
     */
    function maybe_serialize_fallback(mixed $data): string
    {
        if (is_string($data) && \PrestoWorld\Core\Format::isSerialized($data)) {
            return $data;
        }

        return serialize($data);
    }
}