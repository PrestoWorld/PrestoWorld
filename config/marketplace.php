<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Marketplace API Configuration
    |--------------------------------------------------------------------------
    |
    | This config file defines the connection to the PrestoWorld Marketplace
    | API. The marketplace provides extensions, themes, plugins, and
    | a WordPress.org proxy for the PrestoWorld ecosystem.
    |
    */

    // Base URL of the marketplace API
    'api_url' => getenv('MARKETPLACE_API_URL') ?: 'https://prestoworld-marketplace.pages.dev',

    // Optional API key for authenticated operations
    'api_key' => getenv('MARKETPLACE_API_KEY') ?: '',

    // Default items per page when browsing
    'per_page' => 30,

    // Cache TTL for marketplace responses (seconds)
    'cache_ttl' => 3600,
];
