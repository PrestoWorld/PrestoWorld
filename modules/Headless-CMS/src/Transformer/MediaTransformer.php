<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Transformer;

use PrestoWorld\Modules\HeadlessCMS\Serializer\MediaSerializer;

class MediaTransformer
{
    /**
     * Transform a single media item
     */
    public function transform(array $media): array
    {
        return [
            'id' => (int) $media['id'],
            'type' => $media['post_type'] ?? 'attachment',
            'attributes' => [
                'title' => $media['title'] ?? '',
                'slug' => $media['slug'] ?? '',
                'mime_type' => $media['mime_type'] ?? '',
                'url' => $media['file_url'] ?? '',
                'size' => [
                    'width' => (int) ($media['width'] ?? 0),
                    'height' => (int) ($media['height'] ?? 0),
                    'file_size' => (int) ($media['file_size'] ?? 0),
                ],
                'alt_text' => $media['alt_text'] ?? '',
                'caption' => $media['caption'] ?? '',
                'description' => $media['description'] ?? '',
            ],
        ];
    }

    /**
     * Transform collection of media items
     */
    public function transformCollection(array $medias): array
    {
        return array_map(fn($media) => $this->transform($media), $medias);
    }
}