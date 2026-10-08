<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Kses — wp_kses subset (allow-list an toàn, spec 10 §10.4.2).
 */
final class Kses
{
    /** @var array<string, array<string, bool|string>> */
    private const DEFAULT_ALLOWED = [
        'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true],
        'abbr' => ['title' => true],
        'b' => [],
        'blockquote' => ['cite' => true],
        'br' => [],
        'cite' => [],
        'code' => [],
        'del' => ['datetime' => true],
        'em' => [],
        'i' => [],
        'img' => ['src' => true, 'alt' => true, 'title' => true, 'width' => true, 'height' => true],
        'li' => [],
        'ol' => [],
        'p' => [],
        'q' => ['cite' => true],
        's' => [],
        'strong' => [],
        'sub' => [],
        'sup' => [],
        'ul' => [],
    ];

    /** @var array<string, array<string, array<string, bool|string>>> */
    private const POST_ALLOWED = [
        'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true],
        'b' => [],
        'blockquote' => ['cite' => true],
        'code' => [],
        'del' => ['datetime' => true],
        'em' => [],
        'i' => [],
        'img' => ['src' => true, 'alt' => true, 'title' => true, 'width' => true, 'height' => true, 'class' => true],
        'li' => [],
        'ol' => [],
        'p' => [],
        'pre' => [],
        's' => [],
        'strong' => [],
        'sub' => [],
        'sup' => [],
        'ul' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'h5' => [],
        'h6' => [],
        'figure' => [],
        'figcaption' => [],
    ];

    private function __construct()
    {
    }

    /**
     * @param array<string, array<string, bool|string>> $allowed
     */
    public static function filter(string $content, array $allowed = []): string
    {
        $allowedTags = $allowed === [] ? self::DEFAULT_ALLOWED : $allowed;
        $content = self::stripUnsafe($content);
        $pattern = '/<(\/?)([a-zA-Z0-9]+)((?:\s+[^<>]*?)?)([\/]?)>/';

        return preg_replace_callback($pattern, static fn (array $m): string => self::rebuildTag($m, $allowedTags), $content) ?? '';
    }

    public static function post(string $content): string
    {
        return self::filter($content, self::POST_ALLOWED);
    }

    /** @return array<string, array<string, bool|string>> */
    public static function allowedHtml(string $context = 'post'): array
    {
        return $context === 'post' ? self::POST_ALLOWED : self::DEFAULT_ALLOWED;
    }

    /** @return list<string> */
    public static function disallowedTags(): array
    {
        return ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'select', 'button', 'link', 'meta'];
    }

    /**
     * @param array<string, array<string, bool|string>> $allowed
     */
    private static function rebuildTag(array $m, array $allowed): string
    {
        $tag = strtolower($m[2]);
        $closing = (string) ($m[1] ?? '');
        $attrs = (string) ($m[3] ?? '');
        $selfClose = (string) ($m[4] ?? '');

        if (self::isDisallowed($tag)) {
            return '';
        }

        $allowedAttrs = $allowed[$tag] ?? null;
        if ($allowedAttrs === null) {
            return htmlspecialchars('<' . $closing . $tag . $attrs . $selfClose . '>', ENT_QUOTES, 'UTF-8');
        }

        if ($closing !== '') {
            return '</' . $tag . '>';
        }

        $sanitized = '<' . $tag;
        if (preg_match_all('/([a-zA-Z0-9:_-]+)\s*=\s*("[^"]*"|\'[^\']*\')/', $attrs, $matches)) {
            foreach ($matches[1] as $i => $name) {
                $nameLower = strtolower($name);
                if (!self::attrAllowed($nameLower, $allowedAttrs)) {
                    continue;
                }
                $value = trim($matches[2][$i], "\"'");
                $value = htmlspecialchars(self::cleanAttrValue($nameLower, $value), ENT_QUOTES, 'UTF-8');
                $sanitized .= ' ' . $nameLower . '="' . $value . '"';
            }
        }

        $sanitized .= $selfClose !== '' ? ' />' : '>';

        return $sanitized;
    }

    private static function isDisallowed(string $tag): bool
    {
        return in_array($tag, self::disallowedTags(), true);
    }

    /**
     * @param array<string, bool|string> $allowedAttrs
     */
    private static function attrAllowed(string $name, array $allowedAttrs): bool
    {
        return !str_starts_with($name, 'on') && array_key_exists($name, $allowedAttrs);
    }

    private static function cleanAttrValue(string $name, string $value): string
    {
        if ($name === 'href' || $name === 'src') {
            if (preg_match('/^\s*(javascript|vbscript):/i', $value) === 1) {
                return '';
            }
            if (preg_match('/^[a-z][a-z0-9.+-]*:/i', $value) === 1
                && preg_match('/^(https?|ftp|mailto):/i', $value) !== 1
                && !str_starts_with($value, '//')) {
                return '';
            }
        }

        return $value;
    }

    private static function stripUnsafe(string $content): string
    {
        foreach (self::disallowedTags() as $tag) {
            $content = (string) preg_replace('@<' . $tag . '(\s[^>]*)?>.*?</' . $tag . '>@si', '', $content);
            $content = (string) preg_replace('@<' . $tag . '(\s[^>]*)?/?>@i', '', $content);
        }

        return $content;
    }
}