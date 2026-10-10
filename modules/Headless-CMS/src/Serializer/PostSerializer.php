<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Serializer;

use PrestoWorld\Modules\Schema\PostRepository;
use PrestoWorld\Modules\Schema\PostTypeSchemaManager;

class PostSerializer
{
    protected PostRepository $postRepository;
    protected PostTypeSchemaManager $schemaManager;

    public function __construct(
        PostRepository $postRepository,
        PostTypeSchemaManager $schemaManager
    ) {
        $this->postRepository = $postRepository;
        $this->schemaManager = $schemaManager;
    }

    /**
     * Serialize a single post
     */
    public function serialize(array $post, array $options = []): array
    {
        $include = $options['include'] ?? [];
        $locale = $options['locale'] ?? null;

        $data = [
            'id' => (int) $post['id'],
            'post_type' => $post['post_type'],
            'status' => $post['status'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'content' => $post['content'] ?? '',
            'excerpt' => $post['excerpt'] ?? '',
            'author_id' => (int) ($post['author_id'] ?? 0),
            'created_at' => $post['created_at'] ?? null,
            'updated_at' => $post['updated_at'] ?? null,
            'published_at' => $post['published_at'] ?? null,
            'link' => $post['link'] ?? $this->generateLink($post),
        ];

        // Include terms if requested
        if (in_array('terms', $include)) {
            $data['terms'] = $post['terms'] ?? [];
        }

        // Include custom data
        $customData = [];
        foreach ($post as $key => $value) {
            if (!in_array($key, array_keys($data))) {
                $customData[$key] = $value;
            }
        }
        if (!empty($customData)) {
            $data['custom_data'] = $customData;
        }

        // Include translations if available
        if (!empty($post['translations'])) {
            $data['translations'] = $post['translations'];
        }

        return $data;
    }

    /**
     * Serialize multiple posts
     */
    public function serializeCollection(array $posts, array $options = []): array
    {
        return array_map(fn($post) => $this->serialize($post, $options), $posts);
    }

    /**
     * Generate paginated response
     */
    public function paginate(
        array $posts,
        int $total,
        int $perPage,
        int $currentPage,
        array $options = []
    ): array {
        $lastPage = (int) ceil($total / $perPage);
        $from = ($currentPage - 1) * $perPage + 1;
        $to = min($currentPage * $perPage, $total);

        $baseUrl = rtrim($_SERVER['REQUEST_URI'] ?? '/', '?') . '?';
        $queryParams = $_GET ?? [];
        
        return [
            'data' => $this->serializeCollection($posts, $options),
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
            ],
            'links' => [
                'first' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => 1])),
                'last' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $lastPage])),
                'prev' => $currentPage > 1 ? $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $currentPage - 1])) : null,
                'next' => $currentPage < $lastPage ? $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $currentPage + 1])) : null,
            ],
        ];
    }

    protected function generateLink(array $post): string
    {
        $slug = $post['slug'] ?? '';
        if (empty($slug)) {
            return '#';
        }
        $type = $post['post_type'] ?? 'post';
        
        if ($type === 'page') {
            return '/' . ltrim($slug, '/');
        }
        
        return '/' . $type . '/' . ltrim($slug, '/');
    }

    protected function buildUrl(string $baseUrl, array $params): string
    {
        return $baseUrl . http_build_query($params);
    }
}