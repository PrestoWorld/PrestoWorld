<?php

namespace Modules\MarketplaceSDK\Contracts;

/**
 * Interface ExtensionProvider
 * 
 * Strategy Pattern: Implement this to define how your platform 
 * retrieves Themes and Plugins (e.g. from SQL, GitHub, or a flat file).
 */
interface ExtensionProvider
{
    /**
     * Search the inventory.
     * @return \Modules\MarketplaceSDK\Models\Extension[]
     */
    public function search(array $filters): array;

    /**
     * Get a single extension by slug.
     */
    public function getBySlug(string $slug, string $type = 'any'): ?\Modules\MarketplaceSDK\Models\Extension;

    /**
     * Get the total count for pagination.
     */
    public function count(array $filters): int;
}
