<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Generic Block that handles standard WP classes and styles
 */
class GenericBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        if (!empty($this->innerHTML)) {
            return $this->innerHTML;
        }

        $inner = $this->renderInner($context);
        
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<div{$classAttr}{$styleAttr}>{$inner}</div>";
    }
}
