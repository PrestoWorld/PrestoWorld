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
        $kind = $this->attrs['kind'] ?? 'custom';
        
        // Don't render if no label
        if (empty($label)) {
            return '';
        }
        
        // Check if has submenu
        $hasSubmenu = !empty($this->innerBlocks);
        
        // Check if active
        $isActive = $this->attrs['isActive'] ?? false;
        
        // WordPress standard classes
        $liClasses = array_merge(['wp-block-navigation-item'], $this->classes);
        
        // Add has-child class if has submenu
        if ($hasSubmenu) {
            $liClasses[] = 'has-child';
        }
        
        // Add current-menu-item class if active
        if ($isActive) {
            $liClasses[] = 'current-menu-item';
        }
        
        // Add page list specific class if it's a page link
        if ($kind === 'page' || $kind === 'post-type') {
            $liClasses[] = 'wp-block-pages-list__item';
            $liClasses[] = 'open-on-hover-click';
        } else {
            $liClasses[] = 'wp-block-navigation-link';
        }
        
        $classAttr = ' class="' . implode(' ', array_unique($liClasses)) . '"';
        
        // WordPress standard: link classes
        $linkClasses = ['wp-block-navigation-item__content'];
        if ($kind === 'page' || $kind === 'post-type') {
            $linkClasses[] = 'wp-block-pages-list__item__link';
        }
        $linkClassAttr = ' class="' . implode(' ', $linkClasses) . '"';
        
        // Build link attributes
        $linkAttrs = " href=\"" . htmlspecialchars($url, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\"";
        
        if ($isActive) {
            $linkAttrs .= ' aria-current="page"';
        }
        
        if (isset($this->attrs['opensInNewTab']) && $this->attrs['opensInNewTab']) {
            $linkAttrs .= ' target="_blank"';
        }
        
        if (isset($this->attrs['rel'])) {
            $linkAttrs .= ' rel="' . htmlspecialchars($this->attrs['rel']) . '"';
        } elseif (isset($this->attrs['nofollow']) && $this->attrs['nofollow']) {
            $linkAttrs .= ' rel="nofollow"';
        }
        
        if (!empty($title)) {
            $linkAttrs .= ' title="' . htmlspecialchars($title, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '"';
        }
        
        // Build link content
        $linkContent = '<span class="wp-block-navigation-item__label">' . htmlspecialchars($label) . '</span>';
        
        // Add description if available
        if (!empty($this->attrs['description'])) {
            $linkContent .= '<span class="wp-block-navigation-item__description">' . htmlspecialchars($this->attrs['description']) . '</span>';
        }
        
        $html = "<li{$classAttr}><a{$linkClassAttr}{$linkAttrs}>{$linkContent}</a>";
        
        // Add submenu icon if has submenu and showSubmenuIcon is true
        if ($hasSubmenu && ($context['showSubmenuIcon'] ?? false)) {
            $html .= '<span class="wp-block-navigation__submenu-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M6.5 12.4l5.6 5.6c.4.4 1 .4 1.4 0l5.6-5.6c.4-.4.4-1 0-1.4-.4-.4-1-.4-1.4 0L12 17.4 7.5 12.9c-.4-.4-1-.4-1.4 0-.4.4-.4 1 0 1.4z"></path></svg></span>';
        }
        
        // Render submenu if has submenu
        if ($hasSubmenu) {
            $innerHtml = '';
            foreach ($this->innerBlocks as $innerBlock) {
                $innerHtml .= $innerBlock->render($context);
            }
            $html .= "<ul class=\"wp-block-navigation__submenu-container\">{$innerHtml}</ul>";
        }
        
        $html .= '</li>';
        
        return $html;
    }
}
