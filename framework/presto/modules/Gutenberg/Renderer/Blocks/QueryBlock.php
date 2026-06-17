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
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<div{$classAttr}{$styleAttr}>{$inner}</div>";
    }
}
