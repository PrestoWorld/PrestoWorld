<?php

namespace App\Foundation\Marketplace\Contracts;

/**
 * Interface RepositoryProviderInterface
 * 
 * Defines how to retrieve a collection of items (plugins/themes)
 * from a specific source (Local Files, Database, GitHub API).
 */
interface RepositoryProviderInterface
{
    /**
     * Get a unique identifier for this provider.
     */
    public function getProviderId(): string;
    
    /**
     * Fetch a list of all items from the provider.
     * Can optionally filter by type (string), tags (array), or page (int).
     */
    public function fetchAll(array $filters = []): array;
    
    /**
     * Resolve a single item by its slug.
     */
    public function findBySlug(string $slug, string $type = 'any'): ?RepositoryItemInterface;
    
    /**
     * Get the count of total items matching the filters.
     */
    public function count(array $filters = []): int;
}
