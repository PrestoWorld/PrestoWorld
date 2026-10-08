<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Sanitize — tương đương sanitize_* (spec 10 §10.4.2 Group 1).
 */
final class Sanitize
{
    private function __construct()
    {
    }

    /**
     * WP sanitize_text_field.
     */
    public static function text(string $text): string
    {
        $strips = [
            '<[^>]*>',
            '&[^;]*;',
            "[\r\n\t]",
            '%[a-f0-9]{2}',
            '[^A-Za-z0-9 _\-.,~@+=:;\/?\[\]\(\)&"]',
        ];

        $text = trim($text);
        foreach ($strips as $pattern) {
            $text = preg_replace("/{$pattern}/i", '', $text) ?? $text;
        }

        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }

    public static function textarea(string $text): string
    {
        $text = (string) preg_replace('/<[^>]*>/', '', $text) ?? $text;
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        return trim($text);
    }

    /**
     * WP sanitize_title → slug.
     */
    public static function title(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/&.+?;/', '', $slug) ?? $slug;
        $slug = str_replace('_', '-', $slug);
        $slug = preg_replace('/[^%a-z0-9 _-]/', '', $slug) ?? $slug;
        $slug = preg_replace('/\s+/', '-', $slug) ?? $slug;
        $slug = preg_replace('|-+|', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'presto';
    }

    /**
     * WP sanitize_file_name.
     */
    public static function fileName(string $fileName): string
    {
        $specialChars = ['?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '%', '+', chr(0)];

        $fileName = str_replace($specialChars, '', $fileName);
        $fileName = preg_replace('/[\r\n\t -]+/', '-', $fileName) ?? $fileName;
        $fileName = preg_replace('/[\s_]+/', '-', $fileName) ?? $fileName;
        $fileName = preg_replace('/-+/', '-', $fileName) ?? $fileName;
        $fileName = preg_replace("/^\.+/", '', $fileName) ?? $fileName;

        return $fileName;
    }

    public static function email(string $email): string
    {
        $email = strtolower(trim($email));
        $email = preg_replace('/[^a-z0-9+_.@-]/i', '', $email) ?? $email;

        return self::isEmailLike($email) ? $email : '';
    }

    private static function isEmailLike(string $email): bool
    {
        if ($email === '' || !str_contains($email, '@')) {
            return false;
        }

        return (bool) preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $email);
    }

    public static function key(string $key): string
    {
        $key = strtolower($key);
        $key = preg_replace('/[^a-z0-9_\-]/', '', $key) ?? $key;

        return $key;
    }

    /**
     * WP sanitize_user.
     */
    public static function user(string $username, bool $strict = false): string
    {
        $username = preg_replace('/[\r\n\t ]/', '', $username) ?? $username;
        $pattern = $strict ? '/[^a-z0-9]/i' : '/[^a-zA-Z0-9 _.\-@]/';

        $username = preg_replace($pattern, '', $username) ?? $username;

        return trim($username);
    }

    public static function mimeType(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));

        if (!str_contains($mimeType, '/')) {
            return '';
        }

        return preg_replace('/[^a-z0-9\/.+\-_]/', '', $mimeType) ?? '';
    }

    public static function htmlClass(string $class, string $fallback = ''): string
    {
        $class = trim($class);
        $class = preg_replace('/[^a-zA-Z0-9_\ \-]/', '', $class) ?? $class;
        $class = preg_replace('/\s+/', ' ', $class) ?? $class;

        return $class !== '' ? $class : $fallback;
    }

    public static function stripTags(string $text, bool $removeBreaks = false): string
    {
        $text = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $text) ?? $text;
        $text = strip_tags($text);
        if ($removeBreaks) {
            $text = preg_replace('/[\r\n\t ]+/', ' ', $text) ?? $text;
        }

        return trim($text);
    }

    public static function checkUtf8(string $text, bool $strip = false): string
    {
        if ($text === '' || preg_match('//u', $text)) {
            return $text;
        }

        if (!$strip) {
            return '';
        }

        return (string) preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);
    }

    public static function absInt(mixed $maybeInt): int
    {
        return abs((int) $maybeInt);
    }

    public static function slash(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(static fn (mixed $item): mixed => self::slash($item), $value);
        }

        if (is_string($value)) {
            return addslashes($value);
        }

        return $value;
    }

    public static function unslash(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(static fn (mixed $item): mixed => self::unslash($item), $value);
        }

        if (is_string($value)) {
            return stripslashes($value);
        }

        return $value;
    }

    public static function stripslashesDeep(mixed $value): mixed
    {
        return self::unslash($value);
    }
}