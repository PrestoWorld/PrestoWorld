<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Comment;

use PrestoWorld\Core\CommentRepository;
use PrestoWorld\Core\Comment\CommentEntity;

/**
 * CommentQuery — WP_Comment_Query replacement (spec 10 §10.5.3).
 */
final class CommentQuery
{
    /** @var array<string, mixed> */
    private array $args = [];

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /** @return list<CommentEntity> */
    public function comments(): array
    {
        return CommentRepository::query($this->args);
    }

    public function count(): int
    {
        return count($this->comments());
    }

    public function hasComments(): bool
    {
        return $this->comments() !== [];
    }

    /** @return array<string, mixed> */
    public function getArgs(): array
    {
        return $this->args;
    }
}
