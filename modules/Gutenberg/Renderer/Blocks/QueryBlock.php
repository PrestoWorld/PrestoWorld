<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Query Block rendering core/query
 */
class QueryBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $query = $this->attrs['query'] ?? [];
        $posts = [];

        if (isset($context['post_repository'])) {
            $criteria = [
                'post_type' => $query['postType'] ?? 'post',
                'status'    => 'publish'
            ];

            // Support category/taxonomy filtering from context
            // When rendering a category archive, the context contains 'term' data
            if (isset($context['term']) && is_array($context['term'])) {
                $term = $context['term'];
                $taxonomy = $term['taxonomy'] ?? 'category';
                $termId = $term['id'] ?? $term['term_id'] ?? 0;
                if ($termId) {
                    $criteria['tax_query'] = [
                        [
                            'taxonomy' => $taxonomy,
                            'terms' => [$termId],
                            'field' => 'term_id',
                        ],
                    ];
                }
            }

            // Support category slug from context (for category archives)
            if (isset($context['category_slug']) && $context['category_slug'] !== '') {
                $criteria['category'] = $context['category_slug'];
            }

            // Only fetch if repository is available
            $posts = $context['post_repository']->find($criteria);
        }

        $inner = '';
        $hasResults = !empty($posts);

        foreach ($this->innerBlocks as $block) {
            $name = $block->name;

            // Handle conditional blocks
            if ($name === 'core/post-template' && !$hasResults) {
                continue;
            }
            if ($name === 'core/query-no-results' && $hasResults) {
                continue;
            }

            $inner .= $block->render(array_merge($context, ['posts' => $posts]));
        }
        
        // WordPress standard: always include wp-block-query
        $classes = array_merge(['wp-block-query'], $this->classes);
        $classAttr = ' class="' . implode(' ', $classes) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<div{$classAttr}{$styleAttr}>{$inner}</div>";
    }
}
