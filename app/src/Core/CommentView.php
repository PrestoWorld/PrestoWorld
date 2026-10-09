<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\Comment\CommentEntity;

/**
 * CommentView — comment_form/wp_list_comments/get_comment_author (spec 10 §10.4.17).
 */
final class CommentView
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function form(int $postId = 0, array $args = []): void
    {
        $postId = $postId > 0 ? $postId : PostView::getId();
        $actionRaw = $args['action'] ?? '/wp-comments-post.php';
        $action = is_scalar($actionRaw) ? (string) $actionRaw : '/wp-comments-post.php';

        echo '<form action="' . Escape::attr($action) . '" method="post" class="comment-form">';
        echo '<input type="hidden" name="comment_post_ID" value="' . $postId . '" />';
        echo '<p class="comment-form-author"><input type="text" name="author" placeholder="Name" /></p>';
        echo '<p class="comment-form-email"><input type="email" name="email" placeholder="Email" /></p>';
        echo '<p class="comment-form-comment"><textarea name="comment" required></textarea></p>';
        echo '<p class="form-submit"><button type="submit">Post Comment</button></p>';
        echo '</form>';
    }

    /**
     * @param list<CommentEntity> $comments
     * @param array<string, mixed> $args
     */
    public static function list(array $comments = [], array $args = []): void
    {
        if ($comments === []) {
            $comments = CommentRepository::query([
                'post_id' => PostView::getId(),
                'status' => 'approve',
            ]);
        }

        echo '<ol class="comment-list">';
        foreach ($comments as $comment) {
            echo '<li id="comment-' . $comment->comment_ID . '" class="comment">';
            echo '<div class="comment-author">' . Escape::html(self::author($comment)) . '</div>';
            echo '<div class="comment-content">' . Escape::html(self::text($comment)) . '</div>';
            echo '</li>';
        }
        echo '</ol>';
    }

    public static function author(CommentEntity|int|null $comment = null): string
    {
        $entity = self::resolve($comment);

        return $entity === null ? '' : $entity->comment_author;
    }

    public static function text(CommentEntity|int|null $comment = null): string
    {
        $entity = self::resolve($comment);

        return $entity === null ? '' : $entity->comment_content;
    }

    private static function resolve(CommentEntity|int|null $comment): ?CommentEntity
    {
        if ($comment instanceof CommentEntity) {
            return $comment;
        }

        if (is_int($comment)) {
            return CommentRepository::find($comment);
        }

        return null;
    }
}
