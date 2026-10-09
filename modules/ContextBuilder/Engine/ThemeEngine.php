<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Engine;

/**
 * ThemeEngine — interface cho theme engine.
 *
 * Design Pattern: Strategy
 *
 * Cho phép theme (jankx) chạy trên cả WordPress và PrestoWorld.
 * Theme code không biết đang chạy trên engine nào — chỉ gọi interface methods.
 */
interface ThemeEngine
{
    /**
     * Render template file.
     *
     * @param string $templatePath
     * @param array<string, mixed> $data
     * @return string
     */
    public function renderTemplate(string $templatePath, array $data = []): string;

    /**
     * Query posts.
     *
     * @param array<string, mixed> $args
     * @return array<int, array<string, mixed>>
     */
    public function queryPosts(array $args): array;

    /**
     * Get post by ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function getPost(int $id): ?array;

    /**
     * Get terms for post.
     *
     * @param int $postId
     * @param string $taxonomy
     * @return array<int, array<string, mixed>>
     */
    public function getPostTerms(int $postId, string $taxonomy = 'category'): array;

    /**
     * Get featured image URL.
     *
     * @param int $postId
     * @param string $size
     * @return string
     */
    public function getFeaturedImage(int $postId, string $size = 'full'): string;

    /**
     * Get post permalink.
     *
     * @param int $postId
     * @return string
     */
    public function getPermalink(int $postId): string;

    /**
     * Get author display name.
     *
     * @param int $authorId
     * @return string
     */
    public function getAuthorName(int $authorId): string;

    /**
     * Get human-readable date.
     *
     * @param string $date
     * @return string
     */
    public function getHumanReadableDate(string $date): string;

    /**
     * Get excerpt.
     *
     * @param int $postId
     * @param int $length
     * @return string
     */
    public function getExcerpt(int $postId, int $length = 55): string;

    /**
     * Check if engine is available.
     */
    public function isAvailable(): bool;

    /**
     * Get engine name.
     */
    public function getName(): string;
}