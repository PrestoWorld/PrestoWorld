<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class SocialLinkBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $service = $this->attrs['service'] ?? 'wordpress';
        $url = $this->attrs['url'] ?? '#';
        $label = $this->attrs['label'] ?? ucfirst($service);
        $classes = array_merge(["wp-block-social-link", "wp-social-link-{$service}"], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        return "<li{$classAttr}{$styleAttr}><a href=\"{$url}\" rel=\"noopener nofollow\" target=\"_blank\" class=\"wp-block-social-link-anchor\">{$this->innerHTML}</a></li>";
    }
}
