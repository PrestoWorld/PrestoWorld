<?php

namespace App\Foundation\Marketplace;

use App\Foundation\Marketplace\Contracts\RepositoryProviderInterface;
use App\Foundation\Marketplace\Providers\RemoteApiProvider;

/**
 * Class RepositoryManager
 * 
 * Manages all registered PrestoWorld Hub repositories (Official + 3rd Party).
 * Use this class to fetch and install themes/plugins from various sources.
 */
class RepositoryManager
{
    /** @var RepositoryProviderInterface[] */
    protected array $providers = [];
    
    protected array $registeredUrls = [];

    public function __construct()
    {
        // Load default/registered URLs from settings or environment
        $this->loadRegisteredHubs();
    }

    /**
     * Add a custom Hub URL as a repository provider.
     */
    public function addHub(string $url, string $token = ''): void
    {
        if (isset($this->registeredUrls[$url])) {
            return;
        }

        $provider = new RemoteApiProvider($url, $token);
        $this->providers[] = $provider;
        $this->registeredUrls[$url] = true;
    }

    /**
     * Fetch all items (themes and plugins) from all registered hubs.
     */
    public function fetchAll(array $filters = []): array
    {
        $allItems = [];
        foreach ($this->providers as $provider) {
            try {
                $items = $provider->fetchAll($filters);
                // Tag items with their source for UI display
                foreach ($items as &$item) {
                    $item['provider_id'] = $provider->getProviderId();
                }
                $allItems = array_merge($allItems, $items);
            } catch (\Exception $e) {
                // Log and continue to next hub
                error_log("Failed to fetch from Hub: " . $e->getMessage());
            }
        }
        
        // Potential: Global Sort or Filter
        return $allItems;
    }

    /**
     * Find a single extension (plugin/theme) by slug across all hubs.
     */
    public function findBySlug(string $slug, string $type = 'any'): ?array
    {
        foreach ($this->providers as $provider) {
            $item = $provider->findBySlug($slug, $type);
            if ($item) {
                return $item->resolve();
            }
        }
        return null;
    }

    /**
     * Load registered repository URLs from the system store (Draft version).
     */
    protected function loadRegisteredHubs(): void
    {
        // Default official hub
        $this->addHub('https://hub.prestoworld.com/v1');
        
        // Load custom Hubs from database/config
        $customHubs = app('config')->get('marketplace.repositories', []);
        foreach ($customHubs as $hub) {
            $this->addHub($hub['url'], $hub['token'] ?? '');
        }
    }
}
