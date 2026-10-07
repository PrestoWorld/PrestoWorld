<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Post Template Block rendering core/post-template
 */
class PostTemplateBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $posts = $context['posts'] ?? [];
        
        $classes = array_merge(['wp-block-post-template', 'is-layout-flow'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        
        $output = "<ul{$classAttr}>";
        foreach ($posts as $post) {
            $postContent = '';
            $postId = $post['id'] ?? 0;
            
            // Render inner blocks with the current post context
            foreach ($this->innerBlocks as $innerBlock) {
                $postContent .= $innerBlock->render(array_merge($context, ['post' => $post]));
            }
            
            // Standard WP classes for post items
            $postClass = "wp-block-post post-{$postId} post type-post status-publish format-standard hentry";
            $output .= "<li class=\"{$postClass}\">{$postContent}</li>";
        }
        $output .= "</ul>";
        
        return $output;
    }
}
