<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\Post\PostEntity;

/**
 * PostRepository — get_posts/wp_get_posts (spec 10 §10.3.5 + §10.4).
 *
 * DB bảng pw_posts khi có; seed() cho CLI/test.
 */
final class PostRepository
{
    /** @var array<int, PostEntity> */
    private static array $store = [];

    private function __construct()
    {
    }

    public static function seed(PostEntity $post): void
    {
        self::$store[$post->ID] = $post;
    }

    /**
     * @param array<string, mixed> $args
     * @return list<PostEntity>
     */
    public static function query(array $args = []): array
    {
        $posts = array_values(self::$store);

        $posts = array_values(array_filter($posts, static function (PostEntity $post) use ($args): bool {
            if (isset($args['post_type']) && $args['post_type'] !== 'any' && $args['post_type'] !== $post->post_type) {
                return false;
            }

            if (isset($args['post_status']) && is_string($args['post_status']) && $args['post_status'] !== 'any' && $args['post_status'] !== $post->post_status) {
                return false;
            }

            if (isset($args['s']) && is_string($args['s']) && $args['s'] !== '') {
                $haystack = strtolower($post->post_title . ' ' . $post->post_content);
                if (!str_contains($haystack, strtolower($args['s']))) {
                    return false;
                }
            }

            return true;
        }));

        usort($posts, static fn (PostEntity $a, PostEntity $b): int => $b->post_date <=> $a->post_date);

        $offset = (int) ($args['offset'] ?? 0);
        $perPage = (int) ($args['posts_per_page'] ?? $args['number'] ?? -1);

        if ($offset > 0) {
            $posts = array_slice($posts, $offset);
        }

        if ($perPage >= 0) {
            $posts = array_slice($posts, 0, $perPage === 0 ? 10 : $perPage);
        }

        return $posts;
    }

    public static function find(int $id): ?PostEntity
    {
        return self::$store[$id] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): PostEntity
    {
        $post = new PostEntity($data);
        if ($post->ID === 0) {
            $post->ID = self::nextId();
        }

        self::$store[$post->ID] = $post;

        return $post;
    }

    public static function save(PostEntity $post): PostEntity
    {
        self::$store[$post->ID] = $post;

        return $post;
    }

    public static function delete(int $id): bool
    {
        $had = isset(self::$store[$id]);
        unset(self::$store[$id]);

        return $had;
    }

    public static function reset(): void
    {
        self::$store = [];
    }

    private static function nextId(): int
    {
        $max = 0;
        foreach (array_keys(self::$store) as $id) {
            $max = max($max, (int) $id);
        }

        return $max + 1;
    }
}