<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\Error\PrestoError;

/**
 * PostService — wp_insert_post/wp_update_post/wp_delete_post + publish/trash
 * (spec 10 §10.4.6 Group 5). Delegate PostRepository; trả ID WP-style.
 */
final class PostService
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $nowGmt = gmdate('Y-m-d H:i:s');
        $defaults = [
            'post_date' => $now,
            'post_date_gmt' => $nowGmt,
            'post_modified' => $now,
            'post_modified_gmt' => $nowGmt,
        ];

        $post = PostRepository::create($data + $defaults);

        return $post->ID;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(array $data): int|PrestoError
    {
        $rawId = $data['ID'] ?? $data['id'] ?? 0;
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        if ($id <= 0) {
            return new PrestoError('invalid_post', 'Invalid post ID.');
        }

        $post = PostRepository::find($id);
        if ($post === null) {
            return new PrestoError('invalid_post', 'Post does not exist.');
        }

        foreach ($data as $key => $value) {
            if ($key === 'ID' || $key === 'id') {
                continue;
            }
            if (property_exists($post, (string) $key)) {
                $post->{$key} = $value;
            }
        }

        PostRepository::save($post);

        return $post->ID;
    }

    public static function delete(int $id, bool $forceDelete = false): bool
    {
        return PostRepository::delete($id);
    }

    public static function publish(int $id): void
    {
        self::setStatus($id, 'publish');
    }

    public static function trash(int $id): bool
    {
        return self::setStatus($id, 'trash');
    }

    public static function untrash(int $id): bool
    {
        return self::setStatus($id, 'draft');
    }

    public static function sanitize(mixed $post, string $context = 'display'): mixed
    {
        return $post;
    }

    private static function setStatus(int $id, string $status): bool
    {
        $post = PostRepository::find($id);
        if ($post === null) {
            return false;
        }

        $post->post_status = $status;
        PostRepository::save($post);

        return true;
    }
}
