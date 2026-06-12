<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Site Title Block rendering core/site-title
 */
class SiteTitleBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $title = $context['site_title'] ?? 'PrestoWorld';
        
        if (empty(trim($title))) {
            return '';
        }
        
        $url   = $context['site_url'] ?? '/';
        $isLink = $this->attrs['isLink'] ?? true;
        
        // WordPress standard: always include wp-block-site-title
        $classes = array_merge(['wp-block-site-title'], $this->classes);
        
        // Add textAlign class if present
        if (isset($this->attrs['textAlign'])) {
            $classes[] = 'has-text-align-' . $this->attrs['textAlign'];
        }
        
        // Add link color class if present
        if (isset($this->attrs['style']['elements']['link']['color']['text'])) {
            $classes[] = 'has-link-color';
        }
        
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        
        $level = $this->attrs['level'] ?? 1;
        $tag = 0 === $level ? 'p' : 'h' . (int)$level;
        
        if ($isLink) {
            $linkTarget = $this->attrs['linkTarget'] ?? '_self';
            $isCurrentPage = $context['is_current_page'] ?? false;
            $ariaCurrent = $isCurrentPage ? ' aria-current="page"' : '';
            $content = "<a href=\"" . htmlspecialchars($url, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\" target=\"" . htmlspecialchars($linkTarget, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\" rel=\"home\"{$ariaCurrent}>" . htmlspecialchars($title, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "</a>";
        } else {
            $content = htmlspecialchars($title, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
        }
        
        return "<{$tag}{$classAttr}>{$content}</{$tag}>";
    }
}
