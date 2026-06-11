<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Navigation Link Block rendering core/navigation-link
 */
class NavigationLinkBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $label = $this->attrs['label'] ?? 'Link';
        $url   = $this->attrs['url'] ?? '#';
        $title = $this->attrs['title'] ?? '';
        
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . ' wp-block-navigation-item wp-block-navigation-link"' : ' class="wp-block-navigation-item wp-block-navigation-link"';
        
        return "<li{$classAttr}><a class=\"wp-block-navigation-item__content\" href=\"{$url}\" title=\"{$title}\"><span class=\"wp-block-navigation-item__label\">{$label}</span></a></li>";
    }
}
