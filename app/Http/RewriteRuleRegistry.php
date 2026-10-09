<?php

declare(strict_types=1);

namespace App\Http;

/**
 * WordPress-compatible Rewrite Rule Registry
 *
 * Generates and manages rewrite rules similar to WordPress's
 * rewrite.php. Rules are compiled into a fast lookup table
 * for O(1) URL classification.
 *
 * Rule types:
 * - exact: /about → page template
 * - taxonomy: /category/{slug} → category template
 * - post_type: /product/{slug} → single template
 * - archive: /products → archive template
 * - date: /2026/10/09 → date template
 * - author: /author/{slug} → author template
 * - search: /search/{query} → search template
 * - pagination: /page/{n} → paged index
 */
class RewriteRuleRegistry
{
    /** @var array<string, array{type: string, template: string, priority: int}> */
    private array $exactRules = [];

    /** @var array<string, array{type: string, template: string, priority: int}> */
    private array $prefixRules = [];

    /** @var array<string, array{type: string, template: string, priority: int}> */
    private array $regexRules = [];

    /** @var array<string, true> Known taxonomy bases */
    private array $taxonomyBases = [];

    /** @var array<string, true> Known post type slugs */
    private array $postTypeSlugs = [];

    /** @var array<string, true> Known page slugs */
    private array $pageSlugs = [];

    private int $defaultPriority = 10;

    private ?RewriteRuleCache $cache = null;

    public function __construct(
        private ?\Cycle\Database\DatabaseInterface $db = null,
        private string $tablePrefix = 'pw_',
        ?string $cachePath = null,
    ) {
        if ($cachePath !== null) {
            $this->cache = new RewriteRuleCache($cachePath);
        }
    }

    /**
     * Set cache instance
     */
    public function setCache(RewriteRuleCache $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * Get cache instance
     */
    public function getCache(): ?RewriteRuleCache
    {
        return $this->cache;
    }

    /**
     * Load rules from cache if valid
     */
    public function loadFromCache(): bool
    {
        if ($this->cache === null) {
            return false;
        }

        $data = $this->cache->load();
        if ($data === null) {
            return false;
        }

        $this->exactRules = $data['exact'] ?? [];
        $this->prefixRules = $data['prefix'] ?? [];
        $this->regexRules = $data['regex'] ?? [];
        $this->taxonomyBases = $data['taxonomy_bases'] ?? [];
        $this->postTypeSlugs = $data['post_type_slugs'] ?? [];
        $this->pageSlugs = $data['page_slugs'] ?? [];

        return true;
    }

    /**
     * Save rules to cache
     */
    public function saveToCache(): void
    {
        if ($this->cache === null) {
            return;
        }

        $this->cache->save([
            'exact' => $this->exactRules,
            'prefix' => $this->prefixRules,
            'regex' => $this->regexRules,
            'taxonomy_bases' => $this->taxonomyBases,
            'post_type_slugs' => $this->postTypeSlugs,
            'page_slugs' => $this->pageSlugs,
        ]);
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        if ($this->cache !== null) {
            $this->cache->clear();
        }
    }

    /**
     * Register an exact URL rule
     */
    public function addExact(string $pattern, string $template, string $type = 'page', int $priority = 10): void
    {
        $this->exactRules[$pattern] = [
            'type' => $type,
            'template' => $template,
            'priority' => $priority,
        ];
    }

    /**
     * Register a prefix rule (matches /prefix/*)
     */
    public function addPrefix(string $prefix, string $template, string $type = 'archive', int $priority = 10): void
    {
        $this->prefixRules[$prefix] = [
            'type' => $type,
            'template' => $template,
            'priority' => $priority,
        ];
    }

    /**
     * Register a regex rule
     */
    public function addRegex(string $regex, string $template, string $type = 'custom', int $priority = 10): void
    {
        $this->regexRules[$regex] = [
            'type' => $type,
            'template' => $template,
            'priority' => $priority,
        ];
    }

    /**
     * Register a taxonomy base (e.g., 'category', 'post_tag', 'product_cat')
     */
    public function addTaxonomyBase(string $base, ?string $template = null): void
    {
        $this->taxonomyBases[$base] = true;
        $template = $template ?? $base;
        $this->addPrefix($base, $template, 'term_archive', 20);
    }

    /**
     * Register a post type slug (e.g., 'post', 'page', 'product')
     */
    public function addPostTypeSlug(string $slug, ?string $template = null): void
    {
        $this->postTypeSlugs[$slug] = true;
        $template = $template ?? 'single';
        $this->addPrefix($slug, $template, 'single', 15);
    }

    /**
     * Register a page slug
     */
    public function addPageSlug(string $slug, string $template = 'page'): void
    {
        $this->pageSlugs[$slug] = true;
        $this->addExact('/' . $slug, $template, 'page', 30);
    }

    /**
     * Load rules from database (taxonomies, post types, pages)
     */
    public function loadFromDatabase(): void
    {
        if ($this->db === null) {
            return;
        }

        $this->loadTaxonomyBases();
        $this->loadPostTypeSlugs();
        $this->loadPageSlugs();
    }

    /**
     * Match a URL path against registered rules
     *
     * @return array{type: string, template: string, priority: int, matches: array<string, string>}|null
     */
    public function match(string $path): ?array
    {
        $path = $this->normalize($path);

        // 1. Exact match (highest priority)
        if (isset($this->exactRules[$path])) {
            $rule = $this->exactRules[$path];
            return [
                'type' => $rule['type'],
                'template' => $rule['template'],
                'priority' => $rule['priority'],
                'matches' => [],
            ];
        }

        // 2. Prefix match (taxonomy bases, post type slugs)
        $segments = $this->segments($path);
        if ($segments !== []) {
            $first = $segments[0];
            if (isset($this->prefixRules[$first])) {
                $rule = $this->prefixRules[$first];
                return [
                    'type' => $rule['type'],
                    'template' => $rule['template'],
                    'priority' => $rule['priority'],
                    'matches' => ['slug' => $segments[count($segments) - 1] ?? ''],
                ];
            }
        }

        // 3. Regex match (date archives, pagination, etc.)
        foreach ($this->regexRules as $regex => $rule) {
            if (preg_match($regex, $path, $matches) === 1) {
                return [
                    'type' => $rule['type'],
                    'template' => $rule['template'],
                    'priority' => $rule['priority'],
                    'matches' => $matches,
                ];
            }
        }

        return null;
    }

    /**
     * Check if a slug is a known taxonomy base
     */
    public function isTaxonomyBase(string $slug): bool
    {
        return isset($this->taxonomyBases[$slug]);
    }

    /**
     * Check if a slug is a known post type slug
     */
    public function isPostTypeSlug(string $slug): bool
    {
        return isset($this->postTypeSlugs[$slug]);
    }

    /**
     * Check if a slug is a known page slug
     */
    public function isPageSlug(string $slug): bool
    {
        return isset($this->pageSlugs[$slug]);
    }

    /**
     * Get all taxonomy bases
     *
     * @return list<string>
     */
    public function getTaxonomyBases(): array
    {
        return array_keys($this->taxonomyBases);
    }

    /**
     * Get all post type slugs
     *
     * @return list<string>
     */
    public function getPostTypeSlugs(): array
    {
        return array_keys($this->postTypeSlugs);
    }

    /**
     * Get all page slugs
     *
     * @return list<string>
     */
    public function getPageSlugs(): array
    {
        return array_keys($this->pageSlugs);
    }

    /**
     * Generate WordPress-compatible rewrite rules array
     *
     * @return array<string, string>
     */
    public function generateRewriteRules(): array
    {
        $rules = [];

        // Exact rules
        foreach ($this->exactRules as $pattern => $rule) {
            $rules[$pattern] = $rule['template'];
        }

        // Prefix rules (wildcard)
        foreach ($this->prefixRules as $prefix => $rule) {
            $rules[$prefix . '/*'] = $rule['template'];
        }

        // Regex rules
        foreach ($this->regexRules as $regex => $rule) {
            $rules[$regex] = $rule['template'];
        }

        return $rules;
    }

    /**
     * Clear all rules
     */
    public function clear(): void
    {
        $this->exactRules = [];
        $this->prefixRules = [];
        $this->regexRules = [];
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

    private function loadTaxonomyBases(): void
    {
        $termsTable = $this->tablePrefix . 'terms';
        if (!$this->db->hasTable($termsTable)) {
            return;
        }

        try {
            $rows = $this->db->select('taxonomy')
                ->from($termsTable)
                ->distinct()
                ->fetchAll();

            foreach ($rows as $row) {
                $taxonomy = $row['taxonomy'] ?? '';
                if ($taxonomy !== '') {
                    $this->addTaxonomyBase($taxonomy);
                }
            }
        } catch (\Throwable) {
            // Ignore — taxonomy table may be empty or unavailable
        }
    }

    private function loadPostTypeSlugs(): void
    {
        $postsTable = $this->tablePrefix . 'posts';
        if (!$this->db->hasTable($postsTable)) {
            return;
        }

        try {
            $rows = $this->db->select('post_type')
                ->from($postsTable)
                ->distinct()
                ->fetchAll();

            foreach ($rows as $row) {
                $postType = $row['post_type'] ?? '';
                if ($postType !== '' && $postType !== 'page') {
                    $this->addPostTypeSlug($postType);
                }
            }
        } catch (\Throwable) {
            // Ignore
        }
    }

    private function loadPageSlugs(): void
    {
        $postsTable = $this->tablePrefix . 'posts';
        if (!$this->db->hasTable($postsTable)) {
            return;
        }

        try {
            $rows = $this->db->select('slug')
                ->from($postsTable)
                ->where('post_type', 'page')
                ->where('status', 'publish')
                ->fetchAll();

            foreach ($rows as $row) {
                $slug = $row['slug'] ?? '';
                if ($slug !== '') {
                    $this->addPageSlug($slug);
                }
            }
        } catch (\Throwable) {
            // Ignore
        }
    }
}
