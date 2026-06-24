<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class QueryPaginationBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $classes = array_merge(['wp-block-query-pagination'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        $inner = $this->renderInner($context);
        return "<nav{$classAttr}{$styleAttr}{$this->ariaLabel()}>{$inner}</nav>";
    }

    private function ariaLabel(): string
    {
        return ' aria-label="Pagination"';
    }
}
