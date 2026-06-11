<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Post Featured Image Block rendering core/post-featured-image
 */
class PostFeaturedImageBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        
        $img = '<img src="https://picsum.photos/1200/800" alt="Sample Featured Image" />';
        
        if ($this->attrs['isLink'] ?? false) {
            $img = "<a href=\"#\">{$img}</a>";
        }
        
        return "<figure{$classAttr}{$styleAttr}>{$img}</figure>";
    }
}
