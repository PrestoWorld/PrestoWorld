<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Rewrite Rule Cache — caches rewrite rules for fast lookup
 *
 * Stores compiled rewrite rules in a cache file to avoid
 * database queries on every request. Automatically invalidates
 * when content changes.
 */
class RewriteRuleCache
{
    private string $cachePath;
    private ?array $cachedRules = null;
    private ?array $cachedTaxonomyBases = null;
    private ?array $cachedPostTypeSlugs = null;
    private ?array $cachedPageSlugs = null;

    public function __construct(string $cachePath)
    {
        $this->cachePath = $cachePath;
    }

    /**
     * Load cached rules
     *
     * @return array{
     *     exact: array<string, array{type: string, template: string, priority: int}>,
     *     prefix: array<string, array{type: string, template: string, priority: int}>,
     *     regex: array<string, array{type: string, template: string, priority: int}>,
     *     taxonomy_bases: array<string, true>,
     *     post_type_slugs: array<string, true>,
     *     page_slugs: array<string, true>
     * }|null
     */
    public function load(): ?array
    {
        if ($this->cachedRules !== null) {
            return $this->cachedRules;
        }

        if (!file_exists($this->cachePath)) {
            return null;
        }

        $data = json_decode(file_get_contents($this->cachePath), true);
        if (!is_array($data)) {
            return null;
        }

        $this->cachedRules = $data;
        $this->cachedTaxonomyBases = $data['taxonomy_bases'] ?? [];
        $this->cachedPostTypeSlugs = $data['post_type_slugs'] ?? [];
        $this->cachedPageSlugs = $data['page_slugs'] ?? [];

        return $data;
    }

    /**
     * Save rules to cache
     *
     * @param array{
     *     exact: array<string, array{type: string, template: string, priority: int}>,
     *     prefix: array<string, array{type: string, template: string, priority: int}>,
     *     regex: array<string, array{type: string, template: string, priority: int}>,
     *     taxonomy_bases: array<string, true>,
     *     post_type_slugs: array<string, true>,
     *     page_slugs: array<string, true>
     * } $rules
     */
    public function save(array $rules): void
    {
        $this->cachedRules = $rules;
        $this->cachedTaxonomyBases = $rules['taxonomy_bases'] ?? [];
        $this->cachedPostTypeSlugs = $rules['post_type_slugs'] ?? [];
        $this->cachedPageSlugs = $rules['page_slugs'] ?? [];

        $dir = dirname($this->cachePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->cachePath, json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Clear cache
     */
    public function clear(): void
    {
        $this->cachedRules = null;
        $this->cachedTaxonomyBases = null;
        $this->cachedPostTypeSlugs = null;
        $this->cachedPageSlugs = null;

        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    /**
     * Check if cache is valid
     */
    public function isValid(): bool
    {
        if (!file_exists($this->cachePath)) {
            return false;
        }

        $data = json_decode(file_get_contents($this->cachePath), true);
        return is_array($data) && isset($data['exact'], $data['prefix'], $data['regex']);
    }

    /**
     * Get cached taxonomy bases
     *
     * @return array<string, true>
     */
    public function getTaxonomyBases(): array
    {
        $this->load();
        return $this->cachedTaxonomyBases ?? [];
    }

    /**
     * Get cached post type slugs
     *
     * @return array<string, true>
     */
    public function getPostTypeSlugs(): array
    {
        $this->load();
        return $this->cachedPostTypeSlugs ?? [];
    }

    /**
     * Get cached page slugs
     *
     * @return array<string, true>
     */
    public function getPageSlugs(): array
    {
        $this->load();
        return $this->cachedPageSlugs ?? [];
    }

    /**
     * Get cache file path
     */
    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    /**
     * Get cache age in seconds
     */
    public function getAge(): int
    {
        if (!file_exists($this->cachePath)) {
            return 0;
        }

        return time() - filemtime($this->cachePath);
    }
}
