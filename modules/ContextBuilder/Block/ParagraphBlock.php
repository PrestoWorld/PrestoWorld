<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Block;

use PrestoWorld\Modules\ContextBuilder\BaseBlock;

class ParagraphBlock extends BaseBlock
{
    protected string $name = 'presto/paragraph';

    protected array $attributes = [
        'content' => ['type' => 'string', 'default' => ''],
        'align' => ['type' => 'string', 'default' => 'left'],
    ];

    protected string $renderMode = 'ssr';

    public function render(array $attributes, string $content = ''): string
    {
        $align = htmlspecialchars($attributes['align'] ?? 'left', ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars($attributes['content'] ?? $content, ENT_QUOTES, 'UTF-8');

        return "<p style=\"text-align:{$align}\">{$text}</p>";
    }
}
