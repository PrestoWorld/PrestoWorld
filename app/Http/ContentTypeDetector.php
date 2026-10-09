<?php

declare(strict_types=1);

namespace App\Http;

use PrestoWorld\Modules\Schema\PostRepository;
use Cycle\Database\DatabaseInterface;

/**
 * Content Type Detector — fast detection of content types from URL
 *
 * Uses cached slugs and rewrite rules to determine content type
 * in O(1) time before hitting the database.
 *
 * Detection order (fastest first):
 * 1. Exact page slug match (cached)
 * 2. Taxonomy base match (cached)
 * 3. Post type slug match (cached)
 * 4. Date archive pattern (regex)
 * 5. Author/search/feed patterns (regex)
 * 6. Database lookup (slowest)
 */
class ContentTypeDetector
{
    private ?array $cachedPageSlugs = null;
    private ?array $cachedTaxonomyBases = null;
    private ?array $cachedPostTypeSlugs = null;
    private ?array $cachedPostSlugs = null;

    public function __construct(
        private DatabaseInterface $db,
        private PostRepository $postRepository,
        private string $tablePrefix = 'pw_',
    ) {}

    /**
     * Detect content type from URL path
     *
     * @return array{
     *     type: string,
     *     template: string,
     *     id?: int,
     *     slug?: string,
     *     taxonomy?: string,
     *     term_id?: int,
     *     post_type?: string,
     *     author?: string,
     *     year?: int,
     *     month?: int,
     *     day?: int,
     *     page?: int,
     *     query?: string,
     *     confidence: float
     * }
     */
    public function detect(string $path): array
    {
        $path = $this->normalize($path);
        $segments = $this->segments($path);

        // Empty → home
        if ($segments === []) {
            return [
                'type' => 'home',
                'template' => 'index',
                'confidence' => 1.0,
            ];
        }

        $first = $segments[0];
        $last = $segments[count($segments) - 1];

        // 1. Check exact page slug (cached)
        $pageSlug = $this->detectPageSlug($first, $segments);
        if ($pageSlug !== null) {
            return $pageSlug;
        }

        // 2. Check taxonomy base (cached)
        $taxonomy = $this->detectTaxonomy($first, $last, $segments);
        if ($taxonomy !== null) {
            return $taxonomy;
        }

        // 3. Check post type slug (cached)
        $postType = $this->detectPostType($first, $last, $segments);
        if ($postType !== null) {
            return $postType;
        }

        // 4. Check date archive (regex)
        $date = $this->detectDateArchive($segments);
        if ($date !== null) {
            return $date;
        }

        // 5. Check author/search/feed (regex)
        $special = $this->detectSpecialArchive($first, $last, $segments);
        if ($special !== null) {
            return $special;
        }

        // 6. Check pagination
        $pagination = $this->detectPagination($first, $segments);
        if ($pagination !== null) {
            return $pagination;
        }

        // 7. Database lookup for single post/page
        $single = $this->detectSingleFromDatabase($first, $last, $segments);
        if ($single !== null) {
            return $single;
        }

        // 8. Unknown → 404
        return [
            'type' => 'unknown',
            'template' => '404',
            'confidence' => 0.0,
        ];
    }

    /**
     * Fast check: is this a known page slug?
     */
    public function isPageSlug(string $slug): bool
    {
        $this->ensurePageSlugs();
        return isset($this->cachedPageSlugs[$slug]);
    }

    /**
     * Fast check: is this a known taxonomy base?
     */
    public function isTaxonomyBase(string $slug): bool
    {
        $this->ensureTaxonomyBases();
        return isset($this->cachedTaxonomyBases[$slug]);
    }

    /**
     * Fast check: is this a known post type slug?
     */
    public function isPostTypeSlug(string $slug): bool
    {
        $this->ensurePostTypeSlugs();
        return isset($this->cachedPostTypeSlugs[$slug]);
    }

    /**
     * Fast check: is this a known post slug?
     */
    public function isPostSlug(string $slug): bool
    {
        $this->ensurePostSlugs();
        return isset($this->cachedPostSlugs[$slug]);
    }

    /**
     * Invalidate all caches
     */
    public function invalidateCache(): void
    {
        $this->cachedPageSlugs = null;
        $this->cachedTaxonomyBases = null;
        $this->cachedPostTypeSlugs = null;
        $this->cachedPostSlugs = null;
    }

    /**
     * Get all known taxonomy bases
     *
     * @return list<string>
     */
    public function getTaxonomyBases(): array
    {
        $this->ensureTaxonomyBases();
        return array_keys($this->cachedTaxonomyBases);
    }

    /**
     * Get all known post type slugs
     *
     * @return list<string>
     */
    public function getPostTypeSlugs(): array
    {
        $this->ensurePostTypeSlugs();
        return array_keys($this->cachedPostTypeSlugs);
    }

    /**
     * Get all known page slugs
     *
     * @return list<string>
     */
    public function getPageSlugs(): array
    {
        $this->ensurePageSlugs();
        return array_keys($this->cachedPageSlugs);
    }

    /**
     * Get all known post slugs
     *
     * @return list<string>
     */
    public function getPostSlugs(): array
    {
        $this->ensurePostSlugs();
        return array_keys($this->cachedPostSlugs);
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, id?: int, slug?: string, confidence: float}|null
     */
    private function detectPageSlug(string $first, array $segments): ?array
    {
        // Single segment page
        if (count($segments) === 1 && $this->isPageSlug($first)) {
            $page = $this->findPageBySlug($first);
            if ($page !== null) {
                return [
                    'type' => 'page',
                    'template' => 'page',
                    'id' => $page['id'],
                    'slug' => $first,
                    'confidence' => 1.0,
                ];
            }
        }

        // Hierarchical page: /parent/child
        if (count($segments) > 1) {
            $hierarchical = $this->findHierarchicalPage($segments);
            if ($hierarchical !== null) {
                return [
                    'type' => 'page',
                    'template' => 'page',
                    'id' => $hierarchical['id'],
                    'slug' => $hierarchical['slug'],
                    'confidence' => 0.9,
                ];
            }
        }

        return null;
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, taxonomy: string, term_id?: int, slug?: string, confidence: float}|null
     */
    private function detectTaxonomy(string $first, string $last, array $segments): ?array
    {
        if (!$this->isTaxonomyBase($first)) {
            return null;
        }

        // /{taxonomy}/{slug}
        if (count($segments) >= 2) {
            $term = $this->findTermBySlug($first, $last);
            if ($term !== null) {
                return [
                    'type' => 'term_archive',
                    'template' => $first,
                    'taxonomy' => $first,
                    'term_id' => $term['id'],
                    'slug' => $last,
                    'confidence' => 1.0,
                ];
            }
        }

        // /{taxonomy} (taxonomy root)
        if (count($segments) === 1) {
            return [
                'type' => 'term_archive',
                'template' => $first,
                'taxonomy' => $first,
                'confidence' => 0.8,
            ];
        }

        return null;
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, post_type: string, id?: int, slug?: string, confidence: float}|null
     */
    private function detectPostType(string $first, string $last, array $segments): ?array
    {
        if (!$this->isPostTypeSlug($first)) {
            return null;
        }

        // /{post_type}/{slug} → single
        if (count($segments) >= 2) {
            $post = $this->findPostByTypeAndSlug($first, $last);
            if ($post !== null) {
                return [
                    'type' => 'single',
                    'template' => 'single',
                    'post_type' => $first,
                    'id' => $post['id'],
                    'slug' => $last,
                    'confidence' => 1.0,
                ];
            }
        }

        // /{post_type} → post type archive
        if (count($segments) === 1) {
            return [
                'type' => 'post_type_archive',
                'template' => 'archive',
                'post_type' => $first,
                'confidence' => 0.9,
            ];
        }

        return null;
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, year?: int, month?: int, day?: int, confidence: float}|null
     */
    private function detectDateArchive(array $segments): ?array
    {
        $first = $segments[0] ?? '';

        if (preg_match('/^\d{4}$/', $first) !== 1) {
            return null;
        }

        $year = (int) $first;
        $month = null;
        $day = null;

        if (isset($segments[1]) && preg_match('/^\d{2}$/', $segments[1]) === 1) {
            $month = (int) $segments[1];
        }

        if (isset($segments[2]) && preg_match('/^\d{2}$/', $segments[2]) === 1) {
            $day = (int) $segments[2];
        }

        $type = 'date_archive';

        return [
            'type' => $type,
            'template' => 'date',
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'confidence' => 0.95,
        ];
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, author?: string, query?: string, page?: int, confidence: float}|null
     */
    private function detectSpecialArchive(string $first, string $last, array $segments): ?array
    {
        // Author: /author/{slug}
        if ($first === 'author' && count($segments) >= 2) {
            return [
                'type' => 'author_archive',
                'template' => 'author',
                'author' => $last,
                'confidence' => 1.0,
            ];
        }

        // Search: /search/{query}
        if ($first === 'search') {
            return [
                'type' => 'search',
                'template' => 'search',
                'query' => $last,
                'confidence' => 1.0,
            ];
        }

        // Feed: /feed
        if ($first === 'feed') {
            return [
                'type' => 'feed',
                'template' => 'feed',
                'confidence' => 1.0,
            ];
        }

        return null;
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, page: int, confidence: float}|null
     */
    private function detectPagination(string $first, array $segments): ?array
    {
        if ($first !== 'page' || !isset($segments[1])) {
            return null;
        }

        if (preg_match('/^\d+$/', $segments[1]) !== 1) {
            return null;
        }

        return [
            'type' => 'paged',
            'template' => 'index',
            'page' => (int) $segments[1],
            'confidence' => 1.0,
        ];
    }

    /**
     * @param list<string> $segments
     * @return array{type: string, template: string, id?: int, slug?: string, post_type?: string, confidence: float}|null
     */
    private function detectSingleFromDatabase(string $first, string $last, array $segments): ?array
    {
        // Single segment: /{slug} → post or page
        if (count($segments) === 1) {
            $post = $this->findPostBySlug($first);
            if ($post !== null) {
                $type = ($post['post_type'] ?? 'post') === 'page' ? 'page' : 'single';
                $template = $type === 'page' ? 'page' : 'single';
                return [
                    'type' => $type,
                    'template' => $template,
                    'id' => $post['id'],
                    'slug' => $first,
                    'post_type' => $post['post_type'] ?? 'post',
                    'confidence' => 0.9,
                ];
            }
        }

        // Multi segment: /{post_type}/{slug} or /{taxonomy}/{slug}
        if (count($segments) >= 2) {
            // Try as post type single
            $post = $this->findPostByTypeAndSlug($first, $last);
            if ($post !== null) {
                return [
                    'type' => 'single',
                    'template' => 'single',
                    'id' => $post['id'],
                    'slug' => $last,
                    'post_type' => $first,
                    'confidence' => 0.8,
                ];
            }

            // Try as taxonomy term
            $term = $this->findTermBySlug($first, $last);
            if ($term !== null) {
                return [
                    'type' => 'term_archive',
                    'template' => $first,
                    'taxonomy' => $first,
                    'term_id' => $term['id'],
                    'slug' => $last,
                    'confidence' => 0.8,
                ];
            }
        }

        return null;
    }

    private function findPageBySlug(string $slug): ?array
    {
        try {
            $row = $this->db->select('*')
                ->from($this->tablePrefix . 'posts')
                ->where('slug', $slug)
                ->where('post_type', 'page')
                ->where('status', 'publish')
                ->run()
                ->fetch();

            return is_array($row) && $row !== [] ? $row : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param list<string> $segments
     */
    private function findHierarchicalPage(array $segments): ?array
    {
        // Try to find the deepest page in the hierarchy
        $currentSlug = '';
        $parentId = null;

        foreach ($segments as $segment) {
            $currentSlug = $segment;
            try {
                $query = $this->db->select('*')
                    ->from($this->tablePrefix . 'posts')
                    ->where('slug', $currentSlug)
                    ->where('post_type', 'page')
                    ->where('status', 'publish');

                if ($parentId !== null) {
                    $query->where('post_parent', $parentId);
                }

                $row = $query->run()->fetch();

                if (!is_array($row) || $row === []) {
                    return null;
                }

                $parentId = $row['id'];
            } catch (\Throwable) {
                return null;
            }
        }

        return $row ?? null;
    }

    private function findPostBySlug(string $slug): ?array
    {
        try {
            $row = $this->db->select('*')
                ->from($this->tablePrefix . 'posts')
                ->where('slug', $slug)
                ->where('post_type', 'IN', ['post', 'page'])
                ->where('status', 'publish')
                ->run()
                ->fetch();

            return is_array($row) && $row !== [] ? $row : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function findPostByTypeAndSlug(string $postType, string $slug): ?array
    {
        try {
            $row = $this->db->select('*')
                ->from($this->tablePrefix . 'posts')
                ->where('slug', $slug)
                ->where('post_type', $postType)
                ->where('status', 'publish')
                ->run()
                ->fetch();

            return is_array($row) && $row !== [] ? $row : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function findTermBySlug(string $taxonomy, string $slug): ?array
    {
        try {
            $row = $this->db->select('*')
                ->from($this->tablePrefix . 'terms')
                ->where('taxonomy', $taxonomy)
                ->where('slug', $slug)
                ->run()
                ->fetch();

            return is_array($row) && $row !== [] ? $row : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function ensurePageSlugs(): void
    {
        if ($this->cachedPageSlugs !== null) {
            return;
        }

        $this->cachedPageSlugs = [];
        try {
            $rows = $this->db->select('slug')
                ->from($this->tablePrefix . 'posts')
                ->where('post_type', 'page')
                ->where('status', 'publish')
                ->fetchAll();

            foreach ($rows as $row) {
                $slug = $row['slug'] ?? '';
                if ($slug !== '') {
                    $this->cachedPageSlugs[$slug] = true;
                }
            }
        } catch (\Throwable) {
            // Ignore
        }
    }

    private function ensureTaxonomyBases(): void
    {
        if ($this->cachedTaxonomyBases !== null) {
            return;
        }

        $this->cachedTaxonomyBases = [];
        try {
            $rows = $this->db->select('taxonomy')
                ->from($this->tablePrefix . 'terms')
                ->distinct()
                ->fetchAll();

            foreach ($rows as $row) {
                $taxonomy = $row['taxonomy'] ?? '';
                if ($taxonomy !== '') {
                    $this->cachedTaxonomyBases[$taxonomy] = true;
                }
            }
        } catch (\Throwable) {
            // Ignore
        }
    }

    private function ensurePostTypeSlugs(): void
    {
        if ($this->cachedPostTypeSlugs !== null) {
            return;
        }

        $this->cachedPostTypeSlugs = [];
        try {
            $rows = $this->db->select('post_type')
                ->from($this->tablePrefix . 'posts')
                ->distinct()
                ->fetchAll();

            foreach ($rows as $row) {
                $postType = $row['post_type'] ?? '';
                if ($postType !== '' && $postType !== 'page') {
                    $this->cachedPostTypeSlugs[$postType] = true;
                }
            }
        } catch (\Throwable) {
            // Ignore
        }
    }

    private function ensurePostSlugs(): void
    {
        if ($this->cachedPostSlugs !== null) {
            return;
        }

        $this->cachedPostSlugs = [];
        try {
            $rows = $this->db->select('slug')
                ->from($this->tablePrefix . 'posts')
                ->where('post_type', 'post')
                ->where('status', 'publish')
                ->fetchAll();

            foreach ($rows as $row) {
                $slug = $row['slug'] ?? '';
                if ($slug !== '') {
                    $this->cachedPostSlugs[$slug] = true;
                }
            }
        } catch (\Throwable) {
            // Ignore
        }
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
}
