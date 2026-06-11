<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Post Title Block rendering core/post-title
 */
class PostTitleBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = $context['post'] ?? [];
        $title = $post['post_title'] ?? $post['title'] ?? 'Sample Post Title';
        
        $level = $this->attrs['level'] ?? 2;
        $isLink = $this->attrs['isLink'] ?? true;
        
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        
        $url = $post['url'] ?? $post['link'] ?? '#';
        $content = $isLink ? "<a href=\"{$url}\" target=\"_self\">{$title}</a>" : $title;
        return "<h{$level}{$classAttr}>{$content}</h{$level}>";
    }
}
