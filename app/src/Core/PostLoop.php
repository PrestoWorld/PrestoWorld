<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\Post\PostQuery;

/**
 * PostLoop — vòng lặp chính (the_post/have_posts/setup_postdata/query_posts).
 *
 * Quản lý một PostQuery "main" (scoped, reset sau request — spec 10 §10.7.2).
 * Nếu global $wp_query đã có (WPQuery DB shim hoặc PostQuery) thì ưu tiên nó
 * để theme/plugin iterate đúng nguồn dữ liệu.
 */
final class PostLoop
{
    private static ?PostQuery $main = null;

    private function __construct()
    {
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public static function query(array $args): PostQuery
    {
        /** @var array<string, mixed> $normalized */
        $normalized = [];
        foreach ($args as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        $main = new PostQuery($normalized);
        self::$main = $main;
        $GLOBALS['wp_query'] = $main;

        return $main;
    }

    public static function main(): ?PostQuery
    {
        return self::$main;
    }

    public static function havePosts(): bool
    {
        $query = $GLOBALS['wp_query'] ?? self::$main;
        if (is_object($query) && method_exists($query, 'have_posts')) {
            return (bool) $query->have_posts();
        }

        return false;
    }

    public static function thePost(): void
    {
        $query = $GLOBALS['wp_query'] ?? self::$main;
        if (is_object($query) && method_exists($query, 'the_post')) {
            $query->the_post();
        }
    }

    public static function setup(mixed $post): bool
    {
        if ($post === null) {
            return false;
        }

        $GLOBALS['post'] = $post;

        return true;
    }

    public static function current(): mixed
    {
        return $GLOBALS['post'] ?? null;
    }

    public static function reset(): void
    {
        self::$main = null;
    }
}
