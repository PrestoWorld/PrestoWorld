<?php

namespace Modules\MarketplaceSDK\Controllers;

use Modules\MarketplaceSDK\Contracts\ExtensionProvider;
use Modules\MarketplaceSDK\Models\Extension;

/**
 * HubController
 * 
 * Reference implementation for exposing a Marketplace Hub.
 * 3rd parties can fork this and inject their own ExtensionProvider.
 */
class HubController
{
    protected ExtensionProvider $provider;

    public function __construct(ExtensionProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * GET /api/v1/catalog
     * Returns a list of themes and plugins.
     */
    public function catalog()
    {
        $filters = $_GET ?? [];
        $items = $this->provider->search($filters);
        
        $response = [
            'repository' => [
                'name' => config('hub.name', 'My Marketplace'),
                'updated_at' => date('c'),
            ],
            'items' => array_map(fn(Extension $e) => $e->toArray(), $items),
            'pagination' => [
                'total' => $this->provider->count($filters),
                'page'  => (int)($filters['page'] ?? 1),
            ],
        ];

        return response()->json($response);
    }

    /**
     * GET /api/v1/info/{slug}
     * Full metadata for a single item.
     */
    public function info(string $slug)
    {
        $item = $this->provider->getBySlug($slug);
        
        if (!$item) {
            return response()->json(['error' => 'Not Found'], 404);
        }

        return response()->json($item->toArray());
    }

    /**
     * GET /api/v1/download/{slug}
     * Redirects to a secure, possibly signed download link.
     */
    public function download(string $slug)
    {
        $item = $this->provider->getBySlug($slug);
        
        if (!$item) {
            return response()->json(['error' => 'Not Found'], 404);
        }

        // Logic for signed URL or direct download goes here
        return redirect($item->download_url);
    }
}
