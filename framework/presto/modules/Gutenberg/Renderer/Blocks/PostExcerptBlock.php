<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class PostExcerptBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = $context['post'] ?? [];
        $excerpt = $post['post_excerpt'] ?? $post['excerpt'] ?? '';
        if (empty($excerpt)) {
            $content = $post['post_content'] ?? $post['content'] ?? '';
            $excerpt = mb_strlen($content) > 200 ? mb_substr(strip_tags($content), 0, 200) . '...' : strip_tags($content);
        }
        $classes = array_merge(['wp-block-post-excerpt'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        return "<div{$classAttr}{$styleAttr}><p class=\"wp-block-post-excerpt__excerpt\">{$excerpt}</p></div>";
    }
}
