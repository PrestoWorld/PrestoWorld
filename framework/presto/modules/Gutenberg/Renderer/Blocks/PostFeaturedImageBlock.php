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
        $post = $context['post'] ?? [];
        
        // WordPress standard: always include wp-block-post-featured-image
        $classes = array_merge(['wp-block-post-featured-image'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        
        $imgUrl = $post['featured_image_url'] ?? 'https://picsum.photos/1200/800';
        $img = "<img src=\"{$imgUrl}\" alt=\"Sample Featured Image\" />";
        
        $url = $post['url'] ?? $post['link'] ?? '#';
        if ($this->attrs['isLink'] ?? false) {
            $img = "<a href=\"{$url}\">{$img}</a>";
        }
        
        return "<figure{$classAttr}{$styleAttr}>{$img}</figure>";
    }
}
