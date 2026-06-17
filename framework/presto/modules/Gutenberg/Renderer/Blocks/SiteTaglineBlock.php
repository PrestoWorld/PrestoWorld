<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Site Tagline Block rendering core/site-tagline
 */
class SiteTaglineBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $tagline = $context['site_tagline'] ?? 'Digital Innovation Hub';
        
        $classes = array_merge(['wp-block-site-tagline'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        
        return "<p{$classAttr}>" . htmlspecialchars($tagline, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "</p>";
    }
}
