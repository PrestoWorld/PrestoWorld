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
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $logoUrl = $context['site_logo_url'] ?? 'https://picsum.photos/120/120';
        
        $img = "<img src=\"{$logoUrl}\" alt=\"Site Logo\" />";
        
        return "<div{$classAttr}>{$img}</div>";
    }
}
