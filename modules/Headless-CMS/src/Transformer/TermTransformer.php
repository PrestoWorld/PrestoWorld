<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Transformer;

use PrestoWorld\Modules\HeadlessCMS\Serializer\TermSerializer;

class TermTransformer
{
    /**
     * Transform a single term
     */
    public function transform(array $term): array
    {
        return [
            'id' => (int) $term['id'],
            'name' => $term['name'] ?? '',
            'slug' => $term['slug'] ?? '',
            'attributes' => [
                'taxonomy' => $term['taxonomy'] ?? 'category',
                'description' => $term['description'] ?? '',
                'parent' => (int) ($term['parent'] ?? 0),
                'count' => (int) ($term['count'] ?? 0),
                'url' => $term['url'] ?? '',
            ],
        ];
    }

    /**
     * Transform collection of terms
     */
    public function transformCollection(array $terms): array
    {
        return array_map(fn($term) => $this->transform($term), $terms);
    }
}