<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Engine;

/**
 * WordPressEngine — WordPress implementation of ThemeEngine.
 *
 * Uses native WordPress functions when running on WordPress.
 * This allows the same theme code to work on both engines.
 */
class WordPressEngine implements ThemeEngine
{
    public function getName(): string
    {
        return 'wordpress';
    }

    public function isAvailable(): bool
    {
        return function_exists('get_posts') && function_exists('get_template_directory');
    }

    public function renderTemplate(string $templatePath, array $data = []): string
    {
        $resolvedPath = $this->resolveTemplatePath($templatePath);
        if ($resolvedPath === null) {
            throw new \RuntimeException("Template not found: $templatePath");
        }

        ob_start();
        /** @psalm-suppress MixedArgument */
        extract($data);
        include $resolvedPath;
        return (string) ob_get_clean();
    }

    public function queryPosts(array $args): array
    {
        $posts = get_posts($args);
        $result = [];
        foreach ($posts as $post) {
            $result[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'date' => $post->post_date,
                'author' => $post->post_author,
                'status' => $post->post_status,
            ];
        }
        return $result;
    }

    public function getPost(int $id): ?array
    {
        $post = get_post($id);
        if ($post === null) {
            return null;
        }

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'date' => $post->post_date,
            'author' => $post->post_author,
            'status' => $post->post_status,
        ];
    }

    public function getPostTerms(int $postId, string $taxonomy = 'category'): array
    {
        $terms = wp_get_post_terms($postId, $taxonomy);
        $result = [];
        foreach ($terms as $term) {
            $result[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'taxonomy' => $term->taxonomy,
            ];
        }
        return $result;
    }

    public function getFeaturedImage(int $postId, string $size = 'full'): string
    {
        $url = get_the_post_thumbnail_url($postId, $size);
        return $url ?: '';
    }

    public function getPermalink(int $postId): string
    {
        return get_permalink($postId) ?: '';
    }

    public function getAuthorName(int $authorId): string
    {
        return get_the_author_meta('display_name', $authorId) ?: '';
    }

    public function getHumanReadableDate(string $date): string
    {
        return human_time_diff(strtotime($date), current_time('timestamp')) . ' ago';
    }

    public function getExcerpt(int $postId, int $length = 55): string
    {
        $excerpt = get_the_excerpt($postId);
        if ($excerpt === '') {
            $post = get_post($postId);
            $excerpt = $post ? $post->post_content : '';
        }
        return wp_trim_words($excerpt, $length);
    }

    protected function resolveTemplatePath(string $templatePath): ?string
    {
        $candidates = [
            get_stylesheet_directory() . '/' . $templatePath,
            get_template_directory() . '/' . $templatePath,
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}