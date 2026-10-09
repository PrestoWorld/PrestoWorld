<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\Comment\CommentEntity;

/**
 * CommentRepository — get_comments/get_comment/count (spec 10 §10.4.17).
 */
final class CommentRepository
{
    /** @var array<int, CommentEntity> */
    private static array $store = [];

    private static int $nextId = 1;

    private function __construct()
    {
    }

    public static function insert(CommentEntity $comment): CommentEntity
    {
        if ($comment->comment_ID === 0) {
            $comment->comment_ID = self::$nextId++;
        }

        self::$store[$comment->comment_ID] = $comment;

        return $comment;
    }

    public static function find(int $id): ?CommentEntity
    {
        return self::$store[$id] ?? null;
    }

    public static function delete(int $id): bool
    {
        $had = isset(self::$store[$id]);
        unset(self::$store[$id]);

        return $had;
    }

    /**
     * @param array<string, mixed> $args
     * @return list<CommentEntity>
     */
    public static function query(array $args = []): array
    {
        $comments = array_values(self::$store);

        if (isset($args['post_id']) && is_numeric($args['post_id'])) {
            $postId = (int) $args['post_id'];
            if ($postId > 0) {
                $comments = array_filter($comments, static fn (CommentEntity $c): bool => $c->comment_post_ID === $postId);
            }
        }

        if (isset($args['status']) && is_scalar($args['status'])) {
            $status = self::normalizeStatus((string) $args['status']);
            $comments = array_filter($comments, static fn (CommentEntity $c): bool => self::status($c) === $status);
        }

        if (isset($args['author_email']) && is_scalar($args['author_email'])) {
            $email = (string) $args['author_email'];
            $comments = array_filter($comments, static fn (CommentEntity $c): bool => $c->comment_author_email === $email);
        }

        if (isset($args['parent']) && is_numeric($args['parent'])) {
            $parent = (int) $args['parent'];
            $comments = array_filter($comments, static fn (CommentEntity $c): bool => $c->comment_parent === $parent);
        }

        if (isset($args['search']) && is_scalar($args['search']) && (string) $args['search'] !== '') {
            $needle = strtolower((string) $args['search']);
            $comments = array_filter($comments, static fn (CommentEntity $c): bool => str_contains(strtolower($c->comment_content), $needle));
        }

        $comments = array_values($comments);

        $orderRaw = $args['order'] ?? 'DESC';
        $desc = strtoupper(is_scalar($orderRaw) ? (string) $orderRaw : 'DESC') === 'DESC';
        $orderbyRaw = $args['orderby'] ?? 'comment_date_gmt';
        $orderby = is_scalar($orderbyRaw) ? (string) $orderbyRaw : 'comment_date_gmt';
        usort($comments, static function (CommentEntity $a, CommentEntity $b) use ($orderby, $desc): int {
            $cmp = match ($orderby) {
                'comment_ID' => $a->comment_ID <=> $b->comment_ID,
                'comment_author' => $a->comment_author <=> $b->comment_author,
                default => $a->comment_date_gmt <=> $b->comment_date_gmt,
            };

            return $desc ? -$cmp : $cmp;
        });

        $offsetRaw = $args['offset'] ?? 0;
        $offset = is_numeric($offsetRaw) ? (int) $offsetRaw : 0;
        if ($offset > 0) {
            $comments = array_slice($comments, $offset);
        }

        $numberRaw = $args['number'] ?? 0;
        $number = is_numeric($numberRaw) ? (int) $numberRaw : 0;
        if ($number > 0) {
            $comments = array_slice($comments, 0, $number);
        }

        return $comments;
    }

    /**
     * @return array<string, int>
     */
    public static function count(int $postId = 0): array
    {
        $comments = $postId > 0
            ? array_filter(self::$store, static fn (CommentEntity $c): bool => $c->comment_post_ID === $postId)
            : self::$store;

        $counts = [
            'approved' => 0,
            'moderated' => 0,
            'spam' => 0,
            'trash' => 0,
            'total_comments' => count($comments),
        ];

        foreach ($comments as $comment) {
            switch (self::status($comment)) {
                case 'approved':
                    $counts['approved']++;
                    break;
                case 'spam':
                    $counts['spam']++;
                    break;
                case 'trash':
                    $counts['trash']++;
                    break;
                default:
                    $counts['moderated']++;
            }
        }

        return $counts;
    }

    public static function timestamp(CommentEntity|int $comment, string $type = 'comment'): int
    {
        $entity = $comment instanceof CommentEntity ? $comment : self::find($comment);
        if ($entity === null) {
            return 0;
        }

        $date = $type === 'gmt' ? $entity->comment_date_gmt : $entity->comment_date;

        return $date !== '' ? (int) strtotime($date) : 0;
    }

    public static function status(CommentEntity $comment): string
    {
        return self::normalizeStatus($comment->comment_approved);
    }

    public static function reset(): void
    {
        self::$store = [];
        self::$nextId = 1;
    }

    private static function normalizeStatus(string $status): string
    {
        return match ($status) {
            '1', 'approve', 'approved' => 'approved',
            '0', '', 'hold' => 'unapproved',
            'spam' => 'spam',
            'trash' => 'trash',
            default => $status,
        };
    }
}
