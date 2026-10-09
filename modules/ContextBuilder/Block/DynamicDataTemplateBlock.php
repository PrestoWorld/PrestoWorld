<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Block;

use PrestoWorld\Modules\ContextBuilder\BaseBlock;

/**
 * DynamicDataTemplateBlock — renders jankx/dynamic-data-template block.
 *
 * This block defines the template for each item in a dynamic data layout.
 * It contains inner blocks that define the post card structure.
 */
class DynamicDataTemplateBlock extends BaseBlock
{
    protected string $name = 'jankx/dynamic-data-template';

    protected array $attributes = [
        'templateLayout' => ['type' => 'string', 'default' => 'default'],
        'className' => ['type' => 'string', 'default' => ''],
        'thumbnailPosition' => ['type' => 'string', 'default' => 'top'],
        'itemSpacing' => ['type' => 'string', 'default' => 'normal'],
        'backgroundColor' => ['type' => 'string', 'default' => ''],
        'textColor' => ['type' => 'string', 'default' => ''],
    ];

    protected string $renderMode = 'ssr';

    public function render(array $attributes, string $content = ''): string
    {
        $className = htmlspecialchars($attributes['className'] ?? '', ENT_QUOTES, 'UTF-8');
        $templateLayout = htmlspecialchars($attributes['templateLayout'] ?? 'default', ENT_QUOTES, 'UTF-8');

        $classes = ['wp-block-jankx-dynamic-data-template'];
        if ($className !== '') {
            $classes[] = $className;
        }

        return sprintf(
            '<div class="%s" data-template-layout="%s">%s</div>',
            implode(' ', $classes),
            $templateLayout,
            $content,
        );
    }
}