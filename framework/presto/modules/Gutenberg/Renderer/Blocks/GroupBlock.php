<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Group Block rendering core/group
 */
class GroupBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $inner = $this->renderInner($context);
        $tag = $this->attrs['tagName'] ?? 'div';
        
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<{$tag}{$classAttr}{$styleAttr}>{$inner}</{$tag}>";
    }
}
