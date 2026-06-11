<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Blocks;

/**
 * Generic Block that handles standard WP classes and styles
 */
class GenericBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $inner = $this->renderInner($context);
        
        // If it's just a text block without a real name, return content
        if (!$this->name) return $this->innerHTML ?: $inner;

        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        // Default to div wrap if it has classes but no specific handler
        return "<div{$classAttr}{$styleAttr}>" . ($this->innerHTML ?: $inner) . "</div>";
    }
}
