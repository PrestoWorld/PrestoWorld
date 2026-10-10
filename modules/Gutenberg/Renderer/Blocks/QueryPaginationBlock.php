<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * core/query-pagination — PHP port of the fork render callback
 * (packages/block-library/src/query-pagination/index.php).
 */
class QueryPaginationBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        // The fork propagates `showLabel`/`paginationArrow`/`query`/`queryId`
        // to the numeric/previous/next child blocks through block context.
        $childContext = $context;
        $childContext['showLabel']       = $this->attrs['showLabel'] ?? $context['showLabel'] ?? true;
        $childContext['paginationArrow'] = $this->attrs['paginationArrow'] ?? $context['paginationArrow'] ?? 'none';
        $childContext['query']           = $this->attrs['query'] ?? $context['query'] ?? [];
        $childContext['queryId']         = $this->attrs['queryId'] ?? $context['queryId'] ?? null;

        $inner = '';
        foreach ($this->innerBlocks as $block) {
            $inner .= $block->render($childContext);
        }

        if (trim($inner) === '') {
            return '';
        }

        $classes = isset($this->attrs['style']['elements']['link']['color']['text']) ? 'has-link-color' : '';

        $wrapperAttributes = $this->wrapperAttributes([
            'aria-label' => 'Pagination',
            'class'      => $classes,
        ]);

        return sprintf('<nav%1$s>%2$s</nav>', $wrapperAttributes, $inner);
    }
}