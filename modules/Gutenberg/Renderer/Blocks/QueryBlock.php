<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * core/query — PHP port of the fork render callback
 * (packages/block-library/src/query/index.php).
 *
 * The Query block does not render the posts itself; it propagates its
 * `query`/`queryId`/`displayLayout` context down to its inner blocks
 * (core/post-template, core/query-pagination*, ...) which do the work —
 * exactly like the fork.
 */
class QueryBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $childContext = $context;
        $childContext['query']       = $this->attrs['query'] ?? $context['query'] ?? [];
        $childContext['queryId']     = $this->attrs['queryId'] ?? $context['queryId'] ?? null;
        $childContext['displayLayout'] = $this->attrs['displayLayout'] ?? $this->attrs['layout'] ?? $context['displayLayout'] ?? [];
        $childContext['enhancedPagination'] = $this->attrs['enhancedPagination']
            ?? $context['enhancedPagination']
            ?? false;

        $inner = '';
        foreach ($this->innerBlocks as $block) {
            $inner .= $block->render($childContext);
        }

        $wrapperAttributes = $this->wrapperAttributes();

        return sprintf('<div%1$s>%2$s</div>', $wrapperAttributes, $inner);
    }
}