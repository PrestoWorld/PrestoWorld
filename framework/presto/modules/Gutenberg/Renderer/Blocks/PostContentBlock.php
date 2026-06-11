<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Post Content Block rendering core/post-content
 */
class PostContentBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = $context['post'] ?? [];
        $content = $post['post_content'] ?? $post['content'] ?? 'Sample content...';
        
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        
        return "<div{$classAttr}{$styleAttr}>{$content}</div>";
    }
}
