<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class QueryPaginationNumbersBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $classes = array_merge(['wp-block-query-pagination-numbers'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        return "<span{$classAttr}{$styleAttr}>1</span>";
    }
}
