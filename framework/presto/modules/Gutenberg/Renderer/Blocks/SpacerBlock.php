<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Spacer Block rendering core/spacer
 */
class SpacerBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        
        return "<div{$classAttr}{$styleAttr} aria-hidden=\"true\"></div>";
    }
}
