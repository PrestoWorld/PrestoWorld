<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class QueryPaginationPreviousBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $classes = array_merge(['wp-block-query-pagination-previous'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        $label = $this->attrs['label'] ?? 'Previous Page';
        return "<a{$classAttr}{$styleAttr} href=\"#\">{$label}</a>";
    }
}
