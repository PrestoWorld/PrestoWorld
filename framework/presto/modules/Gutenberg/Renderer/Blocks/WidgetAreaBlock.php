<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class WidgetAreaBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $classes = array_merge(['wp-block-widget-area'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        $inner = $this->renderInner($context);
        return "<div{$classAttr}{$styleAttr}>{$inner}</div>";
    }
}
