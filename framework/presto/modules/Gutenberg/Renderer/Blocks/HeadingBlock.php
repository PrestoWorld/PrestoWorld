<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Heading Block rendering core/heading
 */
class HeadingBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        if (!empty($this->innerHTML)) {
            return $this->innerHTML;
        }

        $inner = $this->renderInner($context);
        $level = $this->attrs['level'] ?? 2;
        $tag = 'h' . $level;
        
        $classes = array_merge(['wp-block-heading'], $this->classes);
        if (isset($this->attrs['textAlign'])) {
            $classes[] = 'has-text-align-' . $this->attrs['textAlign'];
        }

        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<{$tag}{$classAttr}{$styleAttr}>{$inner}</{$tag}>";
    }
}
