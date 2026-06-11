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

        // Pass the fetched posts down to inner blocks (specifically post-template)
        $inner = $this->renderInner(array_merge($context, ['posts' => $posts]));
        
        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<div{$classAttr}{$styleAttr}>{$inner}</div>";
    }
}
