<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\Comment\CommentEntity;
use PrestoWorld\Core\Error\PrestoError;

/**
 * CommentService — wp_insert_comment/wp_update_comment & status (spec 10 §10.4.17).
 */
final class CommentService
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $defaults = [
            'comment_date' => date('Y-m-d H:i:s'),
            'comment_date_gmt' => gmdate('Y-m-d H:i:s'),
            'comment_type' => 'comment',
            'comment_approved' => '1',
        ];

        $comment = CommentRepository::insert(new CommentEntity($data + $defaults));

        return $comment->comment_ID;
    }

    /**
     * @param array<string, mixed> $data
     * @return int|PrestoError
     */
    public static function update(array $data): int|PrestoError
    {
        $rawId = $data['comment_ID'] ?? 0;
        $id = is_numeric($rawId) ? (int) $rawId : 0;
        $comment = CommentRepository::find($id);
        if ($comment === null) {
            return new PrestoError('invalid_comment', 'Invalid comment ID.');
        }

        foreach ($data as $key => $value) {
            if ($key === 'comment_ID') {
                continue;
            }
            if (property_exists($comment, (string) $key)) {
                $comment->{$key} = $value;
            }
        }

        CommentRepository::insert($comment);

        return 1;
    }

    public static function delete(int $id, bool $forceDelete = false): bool
    {
        return CommentRepository::delete($id);
    }

    public static function status(int $id): string|false
    {
        $comment = CommentRepository::find($id);
        if ($comment === null) {
            return false;
        }

        return CommentRepository::status($comment);
    }

    public static function setStatus(int $id, string $status): bool
    {
        $comment = CommentRepository::find($id);
        if ($comment === null) {
            return false;
        }

        $comment->comment_approved = match ($status) {
            'approve', 'approved', '1' => '1',
            'spam' => 'spam',
            'trash' => 'trash',
            default => '0',
        };
        CommentRepository::insert($comment);

        return true;
    }

    /**
     * @param array<string, mixed>|CommentEntity $comment
     */
    public static function allow(array|CommentEntity $comment): bool
    {
        return true;
    }

    public static function check(mixed $comment): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $comment
     */
    public static function insert(array $comment): int|PrestoError
    {
        if (!isset($comment['comment_post_ID'])) {
            return new PrestoError('invalid_comment', 'Missing comment_post_ID.');
        }

        return self::create($comment);
    }
}
