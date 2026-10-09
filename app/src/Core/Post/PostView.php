<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Post;

use PrestoWorld\Core\Format;
use PrestoWorld\Core\User\UserEntity;
use PrestoWorld\Core\UserRepository;

/**
 * PostView — output helpers cho vòng lặp (the_content/the_title/get_the_ID...).
 * spec 10 §10.4.6 Group 5. Chạy trên $GLOBALS['post'] hiện tại.
 */
final class PostView
{
    private function __construct()
    {
    }

    public static function getContent(string $moreLink = '', bool $stripTeaser = false): string
    {
        return self::apply('the_content', self::fieldString('post_content'));
    }

    public static function content(string $moreLink = ''): void
    {
        echo self::getContent($moreLink);
    }

    public static function getTitle(mixed $post = null): string
    {
        return self::apply('the_title', self::fieldString('post_title', $post));
    }

    public static function title(string $before = '', string $after = '', bool $display = true): string
    {
        $title = $before . self::getTitle() . $after;
        if ($display) {
            echo $title;
        }

        return $title;
    }

    public static function getExcerpt(mixed $post = null): string
    {
        $excerpt = self::fieldString('post_excerpt', $post);
        if ($excerpt === '') {
            $excerpt = Format::trimExcerpt(self::fieldString('post_content', $post));
        }

        return self::apply('get_the_excerpt', $excerpt);
    }

    public static function excerpt(): void
    {
        echo self::apply('the_excerpt', self::getExcerpt());
    }

    public static function getId(mixed $post = null): int
    {
        return self::fieldInt('ID', $post);
    }

    public static function id(): void
    {
        echo (string) self::getId();
    }

    public static function author(mixed $post = null): string
    {
        $authorId = self::fieldInt('post_author', $post);
        $user = UserRepository::find($authorId);

        return $user instanceof UserEntity ? $user->displayName : '';
    }

    private static function apply(string $hook, string $value): string
    {
        if (!function_exists('apply_filters')) {
            return $value;
        }

        try {
            $filtered = apply_filters($hook, $value);
        } catch (\Throwable) {
            return $value;
        }

        return is_scalar($filtered) ? (string) $filtered : '';
    }

    private static function fieldString(string $key, mixed $post = null): string
    {
        $value = self::field($key, $post);

        return is_scalar($value) ? (string) $value : '';
    }

    private static function fieldInt(string $key, mixed $post = null): int
    {
        $value = self::field($key, $post);

        return is_numeric($value) ? (int) $value : 0;
    }

    private static function field(string $key, mixed $post = null): mixed
    {
        $candidate = $post ?? ($GLOBALS['post'] ?? null);

        if ($candidate instanceof PostEntity) {
            return $candidate->get($key, '');
        }

        if (is_object($candidate)) {
            return $candidate->{$key} ?? '';
        }

        if (is_array($candidate)) {
            return $candidate[$key] ?? '';
        }

        if ($key === 'ID') {
            return 0;
        }

        return '';
    }
}
