<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Button Block rendering core/button
 */
class ButtonBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $inner = $this->renderInner($context);
        
        $classes = array_merge(['wp-block-button'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        
        // The actual <a> or <button> is usually inside the innerHTML if parsed,
        // but if it's from attributes (less common for button content), we need to handle it.
        if (!empty($this->innerHTML)) {
             return $this->innerHTML;
        }

        return "<div{$classAttr}>{$inner}</div>";
    }
}
