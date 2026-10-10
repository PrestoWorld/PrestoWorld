<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Serializer;

class MediaSerializer
{
    /**
     * Serialize a single media item
     */
    public function serialize(array $media): array
    {
        return [
            'id' => (int) $media['id'],
            'post_type' => $media['post_type'],
            'title' => $media['title'] ?? '',
            'slug' => $media['slug'] ?? '',
            'mime_type' => $media['mime_type'] ?? '',
            'file_url' => $media['file_url'] ?? '',
            'file_path' => $media['file_path'] ?? '',
            'file_size' => (int) ($media['file_size'] ?? 0),
            'width' => (int) ($media['width'] ?? 0),
            'height' => (int) ($media['height'] ?? 0),
            'alt_text' => $media['alt_text'] ?? '',
            'caption' => $media['caption'] ?? '',
            'description' => $media['description'] ?? '',
            'created_at' => $media['created_at'] ?? null,
            'updated_at' => $media['updated_at'] ?? null,
        ];
    }

    /**
     * Serialize multiple media items
     */
    public function serializeCollection(array $medias): array
    {
        return array_map(fn($media) => $this->serialize($media), $medias);
    }
}