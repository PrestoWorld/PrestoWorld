<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class TermDescriptionBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $classes = array_merge(['wp-block-term-description'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        return "<div{$classAttr}{$styleAttr}>" . $this->renderInner($context) . "</div>";
    }
}
