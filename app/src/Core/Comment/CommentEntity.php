<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Comment;

/**
 * CommentEntity — WP_Comment-like (spec 10 §10.5).
 */
class CommentEntity
{
    public int $comment_ID = 0;
    public int $comment_post_ID = 0;
    public string $comment_author = '';
    public string $comment_author_email = '';
    public string $comment_author_url = '';
    public string $comment_author_IP = '';
    public string $comment_date = '';
    public string $comment_date_gmt = '';
    public string $comment_content = '';
    public int $comment_karma = 0;
    public string $comment_approved = '1';
    public string $comment_agent = '';
    public string $comment_type = 'comment';
    public int $comment_parent = 0;
    public int $user_id = 0;

    /** @param array<string, mixed> $data */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, (string) $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function to_array(): array
    {
        return [
            'comment_ID' => $this->comment_ID,
            'comment_post_ID' => $this->comment_post_ID,
            'comment_author' => $this->comment_author,
            'comment_author_email' => $this->comment_author_email,
            'comment_author_url' => $this->comment_author_url,
            'comment_content' => $this->comment_content,
            'comment_date' => $this->comment_date,
            'comment_approved' => $this->comment_approved,
            'comment_type' => $this->comment_type,
            'comment_parent' => $this->comment_parent,
            'user_id' => $this->user_id,
        ];
    }
}
