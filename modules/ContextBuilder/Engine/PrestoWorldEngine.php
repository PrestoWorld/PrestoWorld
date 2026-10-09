<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Engine;

use PrestoWorld\Modules\Gutenberg\Parser\BlockParser;
use PrestoWorld\Modules\Gutenberg\Renderer\BlockRenderer;

/**
 * PrestoWorldEngine — PrestoWorld implementation of ThemeEngine.
 *
 * Uses ContextLoader + BlockRenderer to render templates.
 */
class PrestoWorldEngine implements ThemeEngine
{
    protected BlockRenderer $blockRenderer;

    protected string $themeDir;

    protected string $storageDir;

    public function __construct(BlockRenderer $blockRenderer, string $themeDir, string $storageDir = '')
    {
        $this->blockRenderer = $blockRenderer;
        $this->themeDir = $themeDir;
        $this->storageDir = $storageDir;
    }

    public function getName(): string
    {
        return 'prestoworld';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function renderTemplate(string $templatePath, array $data = []): string
    {
        // Resolve template path
        $resolvedPath = $this->resolveTemplatePath($templatePath);
        if ($resolvedPath === null) {
            throw new \RuntimeException("Template not found: $templatePath");
        }

        // Parse HTML template into blocks
        $blocks = BlockParser::parseFile($resolvedPath);

        // Render blocks
        return $this->blockRenderer->render($blocks);
    }

    public function queryPosts(array $args): array
    {
        // Delegate to PostRepository (Schema module)
        // This is a simplified implementation
        $postType = $args['post_type'] ?? 'post';
        $postsPerPage = $args['posts_per_page'] ?? 10;

        // In real implementation, this would use PostRepository
        // For now, return empty array
        return [];
    }

    public function getPost(int $id): ?array
    {
        // Delegate to PostRepository
        return null;
    }

    public function getPostTerms(int $postId, string $taxonomy = 'category'): array
    {
        // Delegate to TermRepository
        return [];
    }

    public function getFeaturedImage(int $postId, string $size = 'full'): string
    {
        // Delegate to MediaService
        return '';
    }

    public function getPermalink(int $postId): string
    {
        // Delegate to PostRepository
        return '';
    }

    public function getAuthorName(int $authorId): string
    {
        // Delegate to UserRepository
        return '';
    }

    public function getHumanReadableDate(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            $minutes = (int) ($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 86400) {
            $hours = (int) ($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 604800) {
            $days = (int) ($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }

        return date('M j, Y', $timestamp);
    }

    public function getExcerpt(int $postId, int $length = 55): string
    {
        // Delegate to PostRepository
        return '';
    }

    protected function resolveTemplatePath(string $templatePath): ?string
    {
        $candidates = [];

        // 1. Storage (user-edited)
        if ($this->storageDir !== '') {
            $candidates[] = $this->storageDir . '/' . $templatePath;
        }

        // 2. Theme (default)
        $candidates[] = $this->themeDir . '/templates/' . $templatePath;
        $candidates[] = $this->themeDir . '/' . $templatePath;

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}