<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Transformer;

use PrestoWorld\Modules\HeadlessCMS\Serializer\PostSerializer;

class PostTransformer
{
    /**
     * Transform a single post
     */
    public function transform(array $post): array
    {
        return [
            'id' => (int) $post['id'],
            'type' => $post['post_type'] ?? 'post',
            'status' => $post['status'] ?? 'publish',
            'attributes' => [
                'title' => $post['title'] ?? '',
                'slug' => $post['slug'] ?? '',
                'content' => strip_tags($post['content'] ?? ''),
                'excerpt' => $post['excerpt'] ?? '',
                'author_id' => (int) ($post['author_id'] ?? 0),
                'created_at' => $post['created_at'] ?? null,
                'updated_at' => $post['updated_at'] ?? null,
                'published_at' => $post['published_at'] ?? null,
                'link' => $post['link'] ?? '#',
            ],
            'relationships' => [
                'terms' => $post['terms'] ?? [],
                'custom_fields' => $post['custom_data'] ?? [],
            ],
        ];
    }

    /**
     * Transform collection of posts
     */
    public function transformCollection(array $posts): array
    {
        return array_map(fn($post) => $this->transform($post), $posts);
    }

    /**
     * Transform related posts collection
     */
    public function transformRelatedCollection(array $posts): array
    {
        // Remove current post from related results
        return array_values($posts);
    }
}