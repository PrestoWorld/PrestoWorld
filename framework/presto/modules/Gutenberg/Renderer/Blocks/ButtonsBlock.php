<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Buttons Block rendering core/buttons
 */
class ButtonsBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $inner = $this->renderInner($context);
        
        $classes = array_merge(['wp-block-buttons'], $this->classes);
        $layout = $this->attrs['layout'] ?? [];
        if (isset($layout['type']) && $layout['type'] === 'flex') {
            $classes[] = 'is-layout-flex';
        }

        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<div{$classAttr}{$styleAttr}>{$inner}</div>";
    }
}
