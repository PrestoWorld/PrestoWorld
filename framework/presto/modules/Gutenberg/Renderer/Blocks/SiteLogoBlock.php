<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Site Logo Block rendering core/site-logo
 */
class SiteLogoBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $logoUrl = $context['site_logo_url'] ?? '';
        
        // Return early if no logo is set
        if (empty($logoUrl)) {
            return '';
        }
        
        $isLink = $this->attrs['isLink'] ?? true;
        $siteUrl = $context['site_url'] ?? '/';
        
        // Build img tag with width/height if specified
        $imgAttr = '';
        if (!empty($this->attrs['width'])) {
            $imgAttr .= " width=\"{$this->attrs['width']}\"";
        }
        if (!empty($this->attrs['height'])) {
            $imgAttr .= " height=\"{$this->attrs['height']}\"";
        }
        $img = "<img src=\"" . htmlspecialchars($logoUrl, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\" alt=\"Site Logo\"{$imgAttr} />";
        
        // Wrap in link if isLink
        if ($isLink) {
            $linkTarget = $this->attrs['linkTarget'] ?? '_self';
            $rel = 'rel="home"';
            $ariaLabel = '';
            
            // Add target and aria-label if linkTarget is _blank
            if ($linkTarget === '_blank') {
                $ariaLabel = ' aria-label="(Home link, opens in a new tab)"';
            }
            
            $img = "<a href=\"" . htmlspecialchars($siteUrl, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\" {$rel}{$ariaLabel} target=\"" . htmlspecialchars($linkTarget, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\">{$img}</a>";
        }
        
        // Build wrapper classes
        $classes = array_merge(['wp-block-site-logo'], $this->classes);
        
        // Add is-default-size class if no width is specified
        if (empty($this->attrs['width'])) {
            $classes[] = 'is-default-size';
        }
        
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        
        return "<div{$classAttr}>{$img}</div>";
    }
}
