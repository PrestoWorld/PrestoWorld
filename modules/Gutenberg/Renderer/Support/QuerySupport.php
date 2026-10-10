<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Support;

/**
 * QuerySupport — PHP port of the WordPress core helpers that the Gutenberg
 * block-library render callbacks rely on:
 *
 *   - build_query_vars_from_query_block()  (wp-includes/blocks.php)
 *   - get_query_pagination_arrow()         (wp-includes/blocks.php)
 *   - paginate_links()                     (subset, wp-includes/general-template.php)
 *
 * The implementations intentionally mirror the fork
 * (/Users/puleeno/Projects/gutenberg/packages/block-library/src/*) so the
 * rendered markup matches Gutenberg as closely as possible.
 */
class QuerySupport
{
    /**
     * Port of build_query_vars_from_query_block().
     *
     * @param array<string, mixed> $attributes Block attributes.
     * @param array<string, mixed> $blockContext Parsed block context.
     * @param int                  $page         Current page.
     * @return array<string, mixed> PostRepository criteria + pagination metadata.
     */
    public static function buildQueryVars(array $attributes, array $blockContext, int $page): array
    {
        $query = $blockContext['query'] ?? $attributes['query'] ?? [];

        $criteria = [
            'post_type' => $query['postType'] ?? 'post',
            'status'    => 'publish',
        ];

        if (!empty($query['order'])) {
            $criteria['order'] = $query['order'];
        }
        if (!empty($query['orderBy'])) {
            $criteria['orderBy'] = $query['orderBy'];
        }

        // Taxonomy filtering.
        $taxQuery = [];
        if (!empty($query['taxQuery']) && is_array($query['taxQuery'])) {
            foreach ($query['taxQuery'] as $taxonomy => $terms) {
                if (empty($terms)) {
                    continue;
                }
                $taxQuery[] = [
                    'taxonomy' => $taxonomy,
                    'terms'    => array_values((array) $terms),
                    'field'    => 'term_id',
                ];
            }
        }
        if ($taxQuery) {
            $criteria['tax_query'] = $taxQuery;
        }

        // Author filtering.
        if (!empty($query['author'])) {
            $criteria['author_id'] = (int) $query['author'];
        }

        // Search filtering.
        if (!empty($query['search'])) {
            $criteria['search'] = (string) $query['search'];
        }

        // Pagination.
        $perPage = (int) ($query['perPage'] ?? 0);
        if ($perPage > 0) {
            $criteria['per_page'] = $perPage;
            $criteria['offset']   = $perPage * max(0, $page - 1);
        }

        // `inherit` uses the global (main) query.
        $criteria['inherit'] = (bool) ($query['inherit'] ?? false);

        return $criteria;
    }

    /**
     * Port of get_query_pagination_arrow().
     *
     * When labels are shown the arrow glyph is omitted (the text label is used
     * instead); otherwise the glyph is rendered as a standalone icon.
     */
    public static function arrow(string $arrow, bool $isNext, bool $showLabel = true): string
    {
        if ($showLabel) {
            return '';
        }

        switch ($arrow) {
            case 'arrow':
                return $isNext ? '→' : '←';
            case 'chevron':
                return $isNext ? '»' : '«';
            case 'none':
            default:
                return '';
        }
    }

    /**
     * Resolve pagination state (current page, page key, max pages) for the
     * query* blocks, using the block context query when available.
     *
     * @return array{0: int, 1: string, 2: int}
     */
    public static function pageInfo(array $context): array
    {
        $query   = $context['query'] ?? [];
        $queryId = $context['queryId'] ?? null;

        $pageKey = ($queryId !== null && $queryId !== '')
            ? 'query-' . $queryId . '-page'
            : 'query-page';

        $page = 1;
        if (!empty($_GET[$pageKey]) && is_numeric($_GET[$pageKey])) {
            $page = max(1, (int) $_GET[$pageKey]);
        }

        $maxPage = (int) ($query['pages'] ?? 0);
        if ($maxPage > 0) {
            return [$page, $pageKey, $maxPage];
        }

        $perPage = (int) ($query['perPage'] ?? 0);
        if ($perPage <= 0) {
            return [$page, $pageKey, 1];
        }

        $repository = $context['post_repository'] ?? null;
        if (!is_object($repository) || !method_exists($repository, 'count')) {
            return [$page, $pageKey, 1];
        }

        $criteria = [
            'post_type' => $query['postType'] ?? 'post',
            'status'    => 'publish',
        ];
        if (!empty($query['taxQuery']) && is_array($query['taxQuery'])) {
            $taxQuery = [];
            foreach ($query['taxQuery'] as $taxonomy => $terms) {
                if (!empty($terms)) {
                    $taxQuery[] = [
                        'taxonomy' => $taxonomy,
                        'terms'    => array_values((array) $terms),
                        'field'    => 'term_id',
                    ];
                }
            }
            if ($taxQuery) {
                $criteria['tax_query'] = $taxQuery;
            }
        }

        $total   = (int) $repository->count($criteria);
        $maxPage = (int) ceil($total / $perPage);

        return [$page, $pageKey, max(1, $maxPage)];
    }

    /**
     * Port of the smallest useful subset of paginate_links(): returns the
     * numeric page links (without prev/next) used by core/query-pagination-numbers.
     *
     * @param array<string, mixed> $args
     */
    public static function paginateLinks(array $args): string
    {
        $current = max(1, (int) ($args['current'] ?? 1));
        $total   = (int) ($args['total'] ?? 0);
        $format  = (string) ($args['format'] ?? '?page=%#%');
        $base    = (string) ($args['base'] ?? '');
        $midSize = isset($args['midSize']) ? (int) $args['midSize'] : 1;
        $endSize = isset($args['endSize']) ? (int) $args['endSize'] : 1;
        $prevText = $args['prev_next'] ?? true;
        $addArgs  = (array) ($args['add_args'] ?? []);

        if ($total < 2) {
            return '';
        }

        $buildLink = static function (int $page, string $label, bool $isCurrent = false) use ($base, $format, $addArgs): string {
            $label = $label;
            if ($isCurrent) {
                return '<span aria-current="page" class="page-numbers current">' . $label . '</span>';
            }
            $url = self::buildPageUrl($page, $base, $format, $addArgs);
            return '<a class="page-numbers" href="' . htmlspecialchars($url, ENT_QUOTES) . '">' . $label . '</a>';
        };

        $links = [];

        // Previous.
        if ($prevText) {
            if ($current > 1) {
                $links[] = $buildLink($current - 1, '« Previous');
            }
        }

        // Page numbers.
        $firstPage = 1;
        $lastPage  = $total;
        $start = max($firstPage, $current - $midSize);
        $end   = min($lastPage, $current + $midSize);

        // End size handling for the tail.
        if ($end + $endSize >= $lastPage) {
            $end = $lastPage;
        }
        if ($start - $endSize <= $firstPage) {
            $start = $firstPage;
        }

        if ($start > $firstPage) {
            $links[] = $buildLink($firstPage, '1');
            if ($start > $firstPage + 1) {
                $links[] = '<span class="page-numbers dots">…</span>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $links[] = $buildLink($i, (string) $i, $i === $current);
        }

        if ($end < $lastPage) {
            if ($end < $lastPage - 1) {
                $links[] = '<span class="page-numbers dots">…</span>';
            }
            $links[] = $buildLink($lastPage, (string) $lastPage);
        }

        // Next.
        if ($prevText) {
            if ($current < $total) {
                $links[] = $buildLink($current + 1, 'Next »');
            }
        }

        return implode('', $links);
    }

    /**
     * Build a pagination URL based on the WP `base`/`format` args.
     *
     * @param array<string, mixed> $addArgs
     */
    protected static function buildPageUrl(int $page, string $base, string $format, array $addArgs): string
    {
        if ($base === '') {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $base       = (string) preg_replace('/[?&].*$/', '', $requestUri);
            if ($base === '') {
                $base = '/';
            }
        }

        $url = $base;

        // Replace %%_%% base placeholder, if present.
        if (str_contains($url, '%_%')) {
            $url = str_replace('%_%', $page === 1 ? '' : $format, $url);
        }

        $url = str_replace('%#%', (string) $page, $url);

        if ($addArgs) {
            $sep = str_contains($url, '?') ? '&' : '?';
            $url .= $sep . http_build_query($addArgs);
        }

        return $url;
    }
}
