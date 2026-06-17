<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Paragraph Block rendering core/paragraph
 */
class ParagraphBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        if (!empty($this->innerHTML)) {
            return $this->innerHTML;
        }

        $inner = $this->renderInner($context);
        
        $classes = array_merge([], $this->classes);
        if (isset($this->attrs['align'])) {
            $classes[] = 'has-text-align-' . $this->attrs['align'];
        }
        if (isset($this->attrs['fontSize'])) {
            $classes[] = 'has-' . $this->attrs['fontSize'] . '-font-size';
        }

        $classAttr = !empty($classes) ? ' class="' . implode(' ', array_unique($classes)) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<p{$classAttr}{$styleAttr}>{$inner}</p>";
    }
}
