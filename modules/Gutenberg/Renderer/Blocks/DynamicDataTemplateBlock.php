<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class DynamicDataTemplateBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $posts = $context['posts'] ?? [];
        $inner = '';

        foreach ($posts as $post) {
            $postContent = '';
            foreach ($this->innerBlocks as $innerBlock) {
                $postContent .= $innerBlock->render(array_merge($context, ['post' => $post]));
            }
            $inner .= $postContent;
        }

        $classes = array_merge(['wp-block-jankx-dynamic-data-template'], $this->classes);
        $classAttr = !empty($classes) ? ' class="' . implode(' ', $classes) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<div{$classAttr}{$styleAttr}>{$inner}</div>";
    }
}
