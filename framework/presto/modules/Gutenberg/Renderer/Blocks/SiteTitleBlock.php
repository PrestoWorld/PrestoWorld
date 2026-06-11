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
        $url   = $context['site_url'] ?? '/';
        $isLink = $this->attrs['isLink'] ?? true;
        
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $content = $isLink ? "<a href=\"{$url}\" rel=\"home\">{$title}</a>" : $title;
        
        return ($this->attrs['level'] ?? 1) === 0 
            ? "<p{$classAttr}>{$content}</p>"
            : "<h1{$classAttr}>{$content}</h1>";
    }
}
