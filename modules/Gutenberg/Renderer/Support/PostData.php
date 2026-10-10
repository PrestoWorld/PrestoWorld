<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Support;

/**
 * PostData — normalises a hydrated PostRepository row into the values the
 * block render callbacks need, tolerating the different field names used
 * across WordPress (`post_title`) and PrestoWorld (`title`) schemas.
 */
class PostData
{
    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>|null
     */
    public static function fromContext(array $context): ?array
    {
        $post = $context['post'] ?? null;
        if (is_array($post) && $post !== []) {
            return $post;
        }

        $postId = $context['postId'] ?? null;
        $repository = $context['post_repository'] ?? null;
        if ($postId && is_object($repository) && method_exists($repository, 'find')) {
            $rows = $repository->find(['id' => $postId]);
            if (!empty($rows)) {
                return $rows[0];
            }
        }

        return null;
    }

    public static function id(array $post): int
    {
        return (int) ($post['id'] ?? $post['ID'] ?? $post['post_id'] ?? 0);
    }

    public static function type(array $post): string
    {
        return (string) ($post['post_type'] ?? 'post');
    }

    public static function title(array $post): string
    {
        return (string) ($post['title'] ?? $post['post_title'] ?? '');
    }

    public static function permalink(array $post): string
    {
        return (string) ($post['link'] ?? $post['permalink'] ?? $post['url'] ?? '#');
    }

    public static function excerpt(array $post): string
    {
        $excerpt = $post['excerpt'] ?? $post['post_excerpt'] ?? '';
        if ($excerpt !== '') {
            return (string) $excerpt;
        }

        $content = self::content($post);
        if ($content === '') {
            return '';
        }

        $stripped = trim(strip_tags($content));

        return mb_strlen($stripped) > 200 ? mb_substr($stripped, 0, 200) . '…' : $stripped;
    }

    public static function content(array $post): string
    {
        return (string) ($post['content'] ?? $post['post_content'] ?? '');
    }

    public static function date(array $post): string
    {
        return (string) ($post['post_date'] ?? $post['date'] ?? $post['created_at'] ?? $post['published_at'] ?? '');
    }

    public static function modifiedDate(array $post): string
    {
        return (string) ($post['post_modified'] ?? $post['modified'] ?? $post['updated_at'] ?? self::date($post));
    }

    public static function authorName(array $post): string
    {
        return (string) ($post['author_name'] ?? $post['author'] ?? 'Admin');
    }

    public static function featuredImageUrl(array $post): string
    {
        return (string) ($post['featured_image_url'] ?? $post['thumbnail'] ?? '');
    }

    /**
     * @param array<string, mixed> $post
     * @return array<int, array<string, mixed>>
     */
    public static function terms(array $post, string $taxonomy): array
    {
        $terms = $post['terms'] ?? [];
        if (!is_array($terms)) {
            return [];
        }

        return array_values(array_filter(
            $terms,
            static fn ($term) => is_array($term) && ($term['taxonomy'] ?? 'category') === $taxonomy
        ));
    }
}
