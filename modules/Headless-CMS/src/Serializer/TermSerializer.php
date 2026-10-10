<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Serializer;

class TermSerializer
{
    /**
     * Serialize a single term
     */
    public function serialize(array $term): array
    {
        return [
            'id' => (int) $term['id'],
            'name' => $term['name'] ?? '',
            'slug' => $term['slug'] ?? '',
            'taxonomy' => $term['taxonomy'] ?? 'category',
            'description' => $term['description'] ?? '',
            'parent' => (int) ($term['parent'] ?? 0),
            'count' => (int) ($term['count'] ?? 0),
            'url' => $term['url'] ?? '',
        ];
    }

    /**
     * Serialize multiple terms
     */
    public function serializeCollection(array $terms): array
    {
        return array_map(fn($term) => $this->serialize($term), $terms);
    }
}