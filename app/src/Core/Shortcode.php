<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Shortcode — has_shortcode/strip_shortcodes/do_shortcode subset (spec 10 §10.4).
 */
final class Shortcode
{
    /** @var array<string, callable> */
    private static array $handlers = [];

    private function __construct()
    {
    }

    public static function add(string $tag, callable $callback): void
    {
        self::$handlers[$tag] = $callback;
    }

    public static function remove(string $tag): void
    {
        unset(self::$handlers[$tag]);
    }

    public static function has(string $content, string $tag): bool
    {
        return (bool) preg_match('/\[/' . preg_quote($tag, '/') . '(?:\s|\])/i', $content);
    }

    /**
     * do_shortcode — xử lý [tag attr=""]...[/tag] qua handler đã đăng ký.
     */
    public static function render(string $content): string
    {
        if (self::$handlers === []) {
            return $content;
        }

        $tagPattern = implode('|', array_map('preg_quote', array_keys(self::$handlers)));
        $pattern = '/\[(' . $tagPattern . ')(?:\s+([^\[\]]*?))?(?:\s*\/\])?(\[[^\[]*])?/';

        return (string) preg_replace_callback($pattern, static fn (array $m): string => self::dispatch($m), $content);
    }

    public static function strip(string $content, ?array $tags = null): string
    {
        $tagList = $tags ?? array_keys(self::$handlers);
        if ($tagList === []) {
            return $content;
        }

        $tagsJoined = implode('|', array_map('preg_quote', $tagList));
        $pattern = '/\[' . $tagsJoined . '[\s\]]?(.*?)]/is';

        return (string) preg_replace($pattern, '', $content);
    }

    /**
     * parse_atts — chuyển "key='value' key2=\"v\" key3" thành mảng.
     */
    public static function atts(string $text): array
    {
        $result = [];
        if (preg_match_all('/([a-zA-Z0-9_-]+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s]+)/', $text, $matches)) {
            foreach ($matches[1] as $i => $key) {
                $value = trim($matches[2][$i], "\"'");
                $result[$key] = $value;
            }
        }

        if (preg_match_all('/(^|\s)([a-zA-Z_-]+)(?=\s|$)/', $text, $flagMatches)) {
            foreach ($flagMatches[2] as $flag) {
                if (!array_key_exists($flag, $result)) {
                    $result[$flag] = true;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<int, string> $m
     */
    private static function dispatch(array $m): string
    {
        $tag = $m[1];
        if (!isset(self::$handlers[$tag])) {
            return $m[0];
        }

        $rawAtts = $m[2] ?? '';
        $atts = self::atts($rawAtts);
        $content = $m[3] ?? '';

        try {
            return (string) call_user_func(self::$handlers[$tag], $atts, $content, $tag);
        } catch (\Throwable $e) {
            trigger_error(sprintf('Shortcode [%s] failed: %s', $tag, $e->getMessage()), E_USER_WARNING);

            return '';
        }
    }
}