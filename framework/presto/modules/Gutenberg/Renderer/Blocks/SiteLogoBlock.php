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
        // WordPress standard: always include wp-block-site-logo
        $classes = array_merge(['wp-block-site-logo'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        
        $logoUrl = $context['site_logo_url'] ?? 'https://picsum.photos/120/120';
        $isLink = $this->attrs['isLink'] ?? true;
        $siteUrl = $context['site_url'] ?? '/';
        
        $img = "<img src=\"{$logoUrl}\" alt=\"Site Logo\" />";
        
        if ($isLink) {
            $img = "<a href=\"{$siteUrl}\" rel=\"home\" aria-current=\"page\">{$img}</a>";
        }
        
        return "<div{$classAttr}>{$img}</div>";
    }
}
