<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class PostAuthorBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = $context['post'] ?? [];
        $author = $post['author'] ?? 'Admin';
        $classes = array_merge(['wp-block-post-author'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        return "<div{$classAttr}{$styleAttr}><div class=\"wp-block-post-author__name\">{$author}</div></div>";
    }
}
