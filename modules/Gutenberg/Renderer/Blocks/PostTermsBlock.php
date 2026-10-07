<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Post Terms Block rendering core/post-terms
 */
class PostTermsBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = $context['post'] ?? null;
        if (!$post) return '';

        $taxonomy = $this->attrs['term'] ?? 'category';
        $terms = $post['terms'] ?? [];
        
        $filtered = array_filter($terms, fn($t) => ($t['taxonomy'] ?? 'category') === $taxonomy);
        
        if (empty($filtered)) return '';

        $links = [];
        foreach ($filtered as $term) {
            $name = $term['name'] ?? '';
            $url  = $term['url'] ?? '#'; // In a real app, generate the term link
            $links[] = '<a href="' . htmlspecialchars($url, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '">' . htmlspecialchars($name, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '</a>';
        }

        $classes = array_merge(['wp-block-post-terms', "taxonomy-{$taxonomy}"], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $separator = $this->attrs['separator'] ?? ', ';

        return '<div' . $classAttr . '>' . implode($separator, $links) . '</div>';
    }
}
