<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Escape — tương đương esc_* và _wp_specialchars (spec 10 §10.4.2 Group 1).
 */
final class Escape
{
    /** @var list<string> */
    private const ALLOWED_SCHEMES = ['http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn'];

    /** @var list<string> */
    private const XML_INVALID = ['<' => '&lt;', '"' => '&quot;', '>' => '&gt;'];

    private function __construct()
    {
    }

    public static function html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public static function attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public static function textarea(string $text): string
    {
        return self::specialChars($text, ENT_QUOTES, 'UTF-8', false);
    }

    public static function js(string $text): string
    {
        $text = str_replace(
            ['\\', "'", '"', '<', '>', '&', "\n", "\r", "\t", "\x00", "\x08", "\x0c"],
            ['\\\\', '\'', '\"', '\\x3C', '\\x3E', '\\x26', '\\n', '\\r', '\\t', '\\x00', '\\x08', '\\x0C'],
            $text,
        );

        return str_replace(['&#038;', '&#x26;', '&amp;'], '&', $text);
    }

    public static function xml(string $text): string
    {
        $text = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text) ?? '';

        return strtr($text, self::XML_INVALID);
    }

    /**
     * WP _wp_specialchars.
     */
    public static function specialChars(string $text, int $quoteStyle = ENT_QUOTES, string $charset = 'UTF-8', bool $doubleEncode = false): string
    {
        if ($text === '') {
            return '';
        }

        $text = (string) preg_replace('/\r\n?/', "\n", $text);

        return htmlspecialchars($text, $quoteStyle, $charset, $doubleEncode);
    }

    public static function url(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $url = (string) preg_replace('/[\x00-\x1F\x7F]/', '', $url);
        $url = self::stripEntity($url);

        if (str_starts_with($url, '#')) {
            return $url;
        }

        if (!self::isAllowedUrl($url)) {
            return '';
        }

        if (self::looksRelative($url)) {
            return $url;
        }

        return $url;
    }

    public static function urlRaw(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $url = (string) preg_replace('/[\x00-\x1F\x7F]/', '', $url);

        if (str_starts_with($url, '#')) {
            return $url;
        }

        if (!self::isAllowedUrl($url)) {
            return '';
        }

        if (self::looksRelative($url)) {
            return $url;
        }

        return $url;
    }

    private static function stripEntity(string $url): string
    {
        $allowed = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 _~-.,+#!%&;:={}|\"\\\\/@?[]^$()';
        $out = '';
        foreach (str_split($url) as $ch) {
            if (strpos($allowed, $ch) !== false) {
                $out .= $ch;
            }
        }

        $out = str_replace(['&amp;', '&#038;', '&#x26;'], '&', $out);

        return $out;
    }

    private static function isAllowedUrl(string $url): bool
    {
        $parsed = parse_url($url);
        if ($parsed === false || ($parsed['scheme'] ?? null) === null) {
            return true;
        }

        return in_array(strtolower($parsed['scheme']), self::ALLOWED_SCHEMES, true);
    }

    private static function looksRelative(string $url): bool
    {
        if (str_starts_with($url, '//')) {
            return true;
        }

        return !(bool) preg_match('#^[a-z][a-z0-9.+-]*://#i', $url);
    }
}