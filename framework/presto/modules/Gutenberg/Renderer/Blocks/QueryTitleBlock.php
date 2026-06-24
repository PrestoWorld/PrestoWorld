<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class QueryTitleBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = $context['post'] ?? [];
        $title = $post['title'] ?? 'Archives';
        $level = $this->attrs['level'] ?? 1;
        $tag = "h{$level}";
        $classes = array_merge(['wp-block-query-title'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        $inner = !empty($this->innerHTML) ? $this->innerHTML : $title;
        return "<{$tag}{$classAttr}{$styleAttr}>{$inner}</{$tag}>";
    }
}
