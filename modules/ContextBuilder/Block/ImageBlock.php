<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Block;

use PrestoWorld\Modules\ContextBuilder\BaseBlock;

class ImageBlock extends BaseBlock
{
    protected string $name = 'presto/image';

    protected array $attributes = [
        'url' => ['type' => 'string', 'default' => ''],
        'alt' => ['type' => 'string', 'default' => ''],
        'width' => ['type' => 'integer', 'default' => 0],
        'height' => ['type' => 'integer', 'default' => 0],
    ];

    protected string $renderMode = 'ssr';

    public function render(array $attributes, string $content = ''): string
    {
        $url = htmlspecialchars($attributes['url'] ?? '', ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($attributes['alt'] ?? '', ENT_QUOTES, 'UTF-8');
        $width = (int) ($attributes['width'] ?? 0);
        $height = (int) ($attributes['height'] ?? 0);

        $size = '';
        if ($width > 0) {
            $size .= " width=\"{$width}\"";
        }
        if ($height > 0) {
            $size .= " height=\"{$height}\"";
        }

        return "<img src=\"{$url}\" alt=\"{$alt}\"{$size} loading=\"lazy\" decoding=\"async\" />";
    }
}
