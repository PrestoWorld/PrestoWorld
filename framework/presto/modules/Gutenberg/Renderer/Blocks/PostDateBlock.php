<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Post Date Block rendering core/post-date
 */
class PostDateBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = $context['post'] ?? [];
        $dateString = $post['post_date'] ?? $post['date'] ?? date('c');
        $timestamp = strtotime($dateString);
        $formattedDate = date('F j, Y', $timestamp);
        $isLink = $this->attrs['isLink'] ?? true;
        
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        
        $content = "<time datetime=\"{$dateString}\">{$formattedDate}</time>";
        if ($isLink) {
            $content = "<a href=\"#\">{$content}</a>";
        }
        
        return "<div{$classAttr}{$styleAttr}>{$content}</div>";
    }
}
