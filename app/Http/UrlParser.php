<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Smart URL Parser — detects content type from URL structure
 *
 * WordPress-compatible rewrite detection:
 * - /{slug}                    → page or post (single)
 * - /{taxonomy}/{slug}         → term archive (category, tag, custom taxonomy)
 * - /{post_type}/{slug}        → custom post type single
 * - /{post_type_archive}       → post type archive
 * - /author/{slug}             → author archive
 * - /{year}/{month}/{day}/{slug} → date-based post
 * - /page/{number}             → pagination
 * - /search/{query}            → search results
 * - /feed                      → RSS feed
 *
 * Fast path: uses regex + prefix matching to classify URL in O(1)
 * before hitting the database.
 */
class UrlParser
{
    /** @var array<string, true> Known taxonomy base slugs (cached from DB) */
    private array $taxonomyBases = [];

    /** @var array<string, true> Known post type slugs (cached from DB) */
    private array $postTypeSlugs = [];

    /** @var array<string, true> Known page slugs (cached from DB) */
    private array $pageSlugs = [];

    /** @var array<string, true> Reserved WordPress slugs */
    private array $reservedSlugs = [
        'author' => true,
        'search' => true,
        'feed' => true,
        'page' => true,
        'wp-json' => true,
        'wp-admin' => true,
        'wp-content' => true,
        'wp-includes' => true,
        'wp-login' => true,
        'wp-signup' => true,
        'wp-activate' => true,
        'trackback' => true,
        'comments' => true,
        'attachment' => true,
        'embed' => true,
        'rest_route' => true,
    ];

    /** @var array<string, true> Date archive prefixes */
    private array $datePatterns = [
        '/^\d{4}\/\d{2}\/\d{2}$/' => 'day',
        '/^\d{4}\/\d{2}$/' => 'month',
        '/^\d{4}$/' => 'year',
    ];

    public function __construct(
        private ?\Closure $taxonomyLoader = null,
        private ?\Closure $postTypeLoader = null,
        private ?\Closure $pageSlugLoader = null,
    ) {}

    /**
     * Parse URL and return structured route info
     *
     * @return array{
     *     type: string,
     *     template: string,
     *     segments: list<string>,
     *     slug: string,
     *     taxonomy?: string,
     *     term_slug?: string,
     *     post_type?: string,
     *     author_slug?: string,
     *     year?: int,
     *     month?: int,
     *     day?: int,
     *     page?: int,
     *     query?: string,
     *     is_explicit: bool
     * }
     */
    public function parse(string $path): array
    {
        $path = $this->normalize($path);
        $segments = $this->segments($path);

        // Empty path → home
        if ($segments === []) {
            return [
                'type' => 'home',
                'template' => 'index',
                'segments' => [],
                'slug' => '',
                'is_explicit' => true,
            ];
        }

        $first = $segments[0];

        // Reserved WordPress slugs
        if (isset($this->reservedSlugs[$first])) {
            return $this->parseReserved($first, $segments);
        }

        // Date archives: /2026/10/09
        if (preg_match('/^\d{4}$/', $first) === 1) {
            return $this->parseDateArchive($segments);
        }

        // Pagination: /page/2
        if ($first === 'page' && isset($segments[1]) && preg_match('/^\d+$/', $segments[1]) === 1) {
            return [
                'type' => 'paged',
                'template' => 'index',
                'segments' => $segments,
                'slug' => '',
                'page' => (int) $segments[1],
                'is_explicit' => false,
            ];
        }

        // Single segment: /{slug} → page, post, or post type archive
        if (count($segments) === 1) {
            return $this->parseSingleSegment($first);
        }

        // Two+ segments: /{taxonomy}/{slug} or /{post_type}/{slug}
        return $this->parseMultiSegment($segments);
    }

    /**
     * Fast check: is this URL a known taxonomy archive pattern?
     * O(1) lookup against cached taxonomy bases.
     */
    public function isTaxonomyBase(string $slug): bool
    {
        $this->ensureTaxonomyBases();
        return isset($this->taxonomyBases[$slug]);
    }

    /**
     * Fast check: is this URL a known post type slug?
     */
    public function isPostTypeSlug(string $slug): bool
    {
        $this->ensurePostTypeSlugs();
        return isset($this->postTypeSlugs[$slug]);
    }

    /**
     * Fast check: is this URL a known page slug?
     */
    public function isPageSlug(string $slug): bool
    {
        $this->ensurePageSlugs();
        return isset($this->pageSlugs[$slug]);
    }

    /**
     * Get all known taxonomy bases (for rewrite rule generation)
     *
     * @return list<string>
     */
    public function getTaxonomyBases(): array
    {
        $this->ensureTaxonomyBases();
        return array_keys($this->taxonomyBases);
    }

    /**
     * Get all known post type slugs
     *
     * @return list<string>
     */
    public function getPostTypeSlugs(): array
    {
        $this->ensurePostTypeSlugs();
        return array_keys($this->postTypeSlugs);
    }

    /**
     * Invalidate cached slugs (call after content changes)
     */
    public function invalidateCache(): void
    {
        $this->taxonomyBases = [];
        $this->postTypeSlugs = [];
        $this->pageSlugs = [];
    }

    private function normalize(string $path): string
    {
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * @return list<string>
     */
    private function segments(string $path): array
    {
        return array_values(
            array_filter(explode('/', trim($path, '/')), static fn (string $s): bool => $s !== '')
        );
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, segments: list<string>, slug: string, is_explicit: bool, query?: string, page?: int}
     */
    private function parseReserved(string $first, array $segments): array
    {
        return match ($first) {
            'author' => $this->parseAuthorArchive($segments),
            'search' => $this->parseSearch($segments),
            'feed' => [
                'type' => 'feed',
                'template' => 'feed',
                'segments' => $segments,
                'slug' => '',
                'is_explicit' => true,
            ],
            'page' => [
                'type' => 'paged',
                'template' => 'index',
                'segments' => $segments,
                'slug' => '',
                'page' => (int) ($segments[1] ?? 1),
                'is_explicit' => false,
            ],
            default => [
                'type' => 'reserved',
                'template' => 'index',
                'segments' => $segments,
                'slug' => $first,
                'is_explicit' => true,
            ],
        };
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, segments: list<string>, slug: string, author_slug?: string, is_explicit: bool}
     */
    private function parseAuthorArchive(array $segments): array
    {
        $authorSlug = $segments[1] ?? '';
        return [
            'type' => 'author',
            'template' => 'author',
            'segments' => $segments,
            'slug' => $authorSlug,
            'author_slug' => $authorSlug,
            'is_explicit' => true,
        ];
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, segments: list<string>, slug: string, query?: string, is_explicit: bool}
     */
    private function parseSearch(array $segments): array
    {
        $query = $segments[1] ?? '';
        return [
            'type' => 'search',
            'template' => 'search',
            'segments' => $segments,
            'slug' => $query,
            'query' => $query,
            'is_explicit' => true,
        ];
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, segments: list<string>, slug: string, year?: int, month?: int, day?: int, is_explicit: bool}
     */
    private function parseDateArchive(array $segments): array
    {
        $year = (int) ($segments[0] ?? 0);
        $month = isset($segments[1]) && preg_match('/^\d{2}$/', $segments[1]) === 1 ? (int) $segments[1] : null;
        $day = isset($segments[2]) && preg_match('/^\d{2}$/', $segments[2]) === 1 ? (int) $segments[2] : null;

        $type = 'date_archive';
        $template = 'date';

        return [
            'type' => $type,
            'template' => $template,
            'segments' => $segments,
            'slug' => '',
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'is_explicit' => true,
        ];
    }

    /**
     * @return array{type: string, template: string, segments: list<string>, slug: string, is_explicit: bool}
     */
    private function parseSingleSegment(string $slug): array
    {
        // Check if it's a known page slug
        if ($this->isPageSlug($slug)) {
            return [
                'type' => 'page',
                'template' => 'page',
                'segments' => [$slug],
                'slug' => $slug,
                'is_explicit' => true,
            ];
        }

        // Check if it's a post type archive slug
        if ($this->isPostTypeSlug($slug)) {
            return [
                'type' => 'post_type_archive',
                'template' => 'archive',
                'segments' => [$slug],
                'slug' => $slug,
                'post_type' => $slug,
                'is_explicit' => true,
            ];
        }

        // Default: could be a post or page — let content lookup decide
        return [
            'type' => 'single',
            'template' => 'single',
            'segments' => [$slug],
            'slug' => $slug,
            'is_explicit' => false,
        ];
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, segments: list<string>, slug: string, taxonomy?: string, term_slug?: string, post_type?: string, is_explicit: bool}
     */
    private function parseMultiSegment(array $segments): array
    {
        $first = $segments[0];
        $last = $segments[count($segments) - 1];

        // Check if first segment is a known taxonomy base
        if ($this->isTaxonomyBase($first)) {
            return [
                'type' => 'term_archive',
                'template' => $first,
                'segments' => $segments,
                'slug' => $last,
                'taxonomy' => $first,
                'term_slug' => $last,
                'is_explicit' => true,
            ];
        }

        // Check if first segment is a post type slug (custom post type single)
        if ($this->isPostTypeSlug($first)) {
            return [
                'type' => 'single',
                'template' => 'single',
                'segments' => $segments,
                'slug' => $last,
                'post_type' => $first,
                'is_explicit' => false,
            ];
        }

        // Fallback: treat as hierarchical page or unknown
        return [
            'type' => 'unknown',
            'template' => 'index',
            'segments' => $segments,
            'slug' => $last,
            'is_explicit' => false,
        ];
    }

    private function ensureTaxonomyBases(): void
    {
        if ($this->taxonomyBases !== [] || $this->taxonomyLoader === null) {
            return;
        }
        $bases = ($this->taxonomyLoader)();
        foreach ($bases as $base) {
            $this->taxonomyBases[$base] = true;
        }
    }

    private function ensurePostTypeSlugs(): void
    {
        if ($this->postTypeSlugs !== [] || $this->postTypeLoader === null) {
            return;
        }
        $slugs = ($this->postTypeLoader)();
        foreach ($slugs as $slug) {
            $this->postTypeSlugs[$slug] = true;
        }
    }

    private function ensurePageSlugs(): void
    {
        if ($this->pageSlugs !== [] || $this->pageSlugLoader === null) {
            return;
        }
        $slugs = ($this->pageSlugLoader)();
        foreach ($slugs as $slug) {
            $this->pageSlugs[$slug] = true;
        }
    }
}
