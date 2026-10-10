<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

use PrestoWorld\Modules\Gutenberg\Renderer\Support\QuerySupport;

/**
 * core/query-pagination-numbers — PHP port of the fork render callback
 * (packages/block-library/src/query-pagination-numbers/index.php).
 */
class QueryPaginationNumbersBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        [$page, $pageKey, $maxPage] = QuerySupport::pageInfo($context);

        $content = QuerySupport::paginateLinks([
            'format'    => '?' . $pageKey . '=%#%',
            'current'   => $page,
            'total'     => $maxPage,
            'mid_size'  => $this->attrs['midSize'] ?? 1,
            'end_size'  => 1,
            'prev_next' => false,
        ]);

        if ($content === '') {
            return '';
        }

        $wrapperAttributes = $this->wrapperAttributes();

        return sprintf('<div%1$s>%2$s</div>', $wrapperAttributes, $content);
    }
}