<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Column Block rendering core/column
 */
class ColumnBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $inner = $this->renderInner($context);
        
        $classes = array_merge(['wp-block-column'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        
        $styleAttr = '';
        if (!empty($this->styles)) {
            $styleAttr = ' style="' . implode(';', $this->styles) . '"';
        }

        return "<div{$classAttr}{$styleAttr}>{$inner}</div>";
    }
}
