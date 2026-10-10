<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

use PrestoWorld\Modules\Gutenberg\Renderer\Support\QuerySupport;

/**
 * core/query-pagination-previous — PHP port of the fork render callback
 * (packages/block-library/src/query-pagination-previous/index.php).
 */
class QueryPaginationPreviousBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        [$page, $pageKey, $maxPage] = QuerySupport::pageInfo($context);

        $showLabel    = (bool) ($context['showLabel'] ?? true);
        $labelText    = !empty($this->attrs['label']) ? (string) $this->attrs['label'] : 'Previous Page';
        $paginationArrow = QuerySupport::arrow((string) ($context['paginationArrow'] ?? 'none'), false, $showLabel);

        if ($showLabel) {
            $label = $labelText;
        } else {
            // Icon-only navigation: the arrow glyph replaces the label.
            $label = $paginationArrow;
        }

        $wrapperAttributes = $this->wrapperAttributes(['aria-label' => $labelText]);

        if ($page <= 1 || $page > $maxPage) {
            return '';
        }

        $href = $_SERVER['REQUEST_URI'] ?? '/';
        $href = (string) preg_replace('/[?&].*$/', '', $href);
        $href .= (str_contains($href, '?') ? '&' : '?') . $pageKey . '=' . ($page - 1);

        return sprintf(
            '<a href="%1$s"%2$s>%3$s</a>',
            htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $wrapperAttributes,
            $label
        );
    }
}