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
        
        if (empty($title)) {
            return '';
        }
        
        $level = $this->attrs['level'] ?? 2;
        $isLink = $this->attrs['isLink'] ?? true;
        
        // WordPress standard: always include wp-block-post-title
        $classes = array_merge(['wp-block-post-title'], $this->classes);
        
        // Add textAlign class if present
        if (isset($this->attrs['textAlign'])) {
            $classes[] = 'has-text-align-' . $this->attrs['textAlign'];
        }
        
        // Add link color class if present
        if (isset($this->attrs['style']['elements']['link']['color']['text'])) {
            $classes[] = 'has-link-color';
        }
        
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        
        $url = $post['url'] ?? $post['link'] ?? '#';
        
        if ($isLink) {
            $linkTarget = $this->attrs['linkTarget'] ?? '_self';
            $rel = !empty($this->attrs['rel']) ? 'rel="' . htmlspecialchars($this->attrs['rel'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '"' : '';
            $content = "<a href=\"" . htmlspecialchars($url, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\" target=\"" . htmlspecialchars($linkTarget, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\" {$rel}>" . htmlspecialchars($title, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "</a>";
        } else {
            $content = htmlspecialchars($title, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
        }
        
        $tag = 0 === $level ? 'p' : 'h' . (int)$level;
        return "<{$tag}{$classAttr}>{$content}</{$tag}>";
    }
}
