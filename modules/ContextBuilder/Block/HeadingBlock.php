<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Block;

use PrestoWorld\Modules\ContextBuilder\BaseBlock;

class HeadingBlock extends BaseBlock
{
    protected string $name = 'presto/heading';

    protected array $attributes = [
        'content' => ['type' => 'string', 'default' => ''],
        'level' => ['type' => 'integer', 'default' => 2],
        'color' => ['type' => 'string', 'default' => '#000000'],
    ];

    protected string $renderMode = 'ssr';

    public function render(array $attributes, string $content = ''): string
    {
        $level = (int) ($attributes['level'] ?? 2);
        $level = max(1, min(6, $level));
        $color = htmlspecialchars($attributes['color'] ?? '#000000', ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars($attributes['content'] ?? $content, ENT_QUOTES, 'UTF-8');

        $tag = 'h' . $level;

        return "<{$tag} style=\"color:{$color}\">{$text}</{$tag}>";
    }
}
