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
        $label = $this->attrs['label'] ?? '';
        $url   = $this->attrs['url'] ?? '#';
        $title = $this->attrs['title'] ?? '';
        $rel   = $this->attrs['rel'] ?? '';

        $classes = array_merge(['wp-block-navigation-item'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        
        $linkClasses = ['wp-block-navigation-item__content'];
        $linkClassAttr = ' class="' . implode(' ', $linkClasses) . '"';
        
        $titleAttr = !empty($title) ? ' title="' . htmlspecialchars($title, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '"' : '';
        $relAttr   = !empty($rel) ? ' rel="' . htmlspecialchars($rel, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '"' : '';

        $content = "<a{$linkClassAttr} href=\"" . htmlspecialchars($url, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\"{$titleAttr}{$relAttr}>";
        $content .= "<span class=\"wp-block-navigation-item__label\">" . htmlspecialchars($label, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "</span>";
        $content .= "</a>";

        return "<li{$classAttr}>{$content}</li>";
    }
}
