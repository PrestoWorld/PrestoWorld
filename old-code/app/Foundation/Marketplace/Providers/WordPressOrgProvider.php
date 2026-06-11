<?php

declare(strict_types=1);

namespace App\Foundation\Marketplace\Providers;

use Prestoworld\MarketplaceSdk\Contracts\RepositoryProviderInterface;
use Prestoworld\MarketplaceSdk\Contracts\RepositoryItemInterface;

/**
 * WordPress.org API Provider
 * 
 * Direct integration with WordPress.org plugin/theme API
 */
class WordPressOrgProvider implements RepositoryProviderInterface
{
    protected string $apiUrl;
    protected string $type;

    public function __construct(string $type = 'plugin')
    {
        $this->type = $type;
        $this->apiUrl = $type === 'plugin' 
            ? 'https://api.wordpress.org/plugins/info/1.2/'
            : 'https://api.wordpress.org/themes/info/1.2/';
    }

    public function getProviderId(): string
    {
        return 'wordpress.org';
    }

    /**
     * Fetch items from WordPress.org API
     * @return array ['items' => [], 'pagination' => ['page' => int, 'per_page' => int, 'total' => int, 'total_pages' => int]]
     */
    public function fetchAll(array $filters = []): array
    {
        $browse = $filters['browse'] ?? 'featured';
        $page = (int)($filters['page'] ?? 1);
        $perPage = (int)($filters['per_page'] ?? 12);

        // WordPress.org API uses specific browse values
        $validBrowse = ['featured', 'popular', 'updated', 'new', 'beta', 'favorites', 'search'];
        if (!in_array($browse, $validBrowse)) {
            $browse = 'featured';
        }

        $params = [
            'action' => $this->type === 'plugin' ? 'query_plugins' : 'query_themes',
            'request[per_page]' => $perPage,
            'request[page]' => $page,
            'request[browse]' => $browse,
        ];

        if (!empty($filters['search'])) {
            $params['request[search]'] = $filters['search'];
            $params['request[browse]'] = 'search';
        }

        $query = http_build_query($params);
        $endpoint = "{$this->apiUrl}?{$query}";

        error_log('[WordPressOrg] API URL: ' . $endpoint);

        $response = $this->makeRequest($endpoint);

        if (empty($response['plugins']) && empty($response['themes'])) {
            return [
                'items' => [],
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 0,
                ],
            ];
        }

        $items = $this->type === 'plugin' 
            ? ($response['plugins'] ?? [])
            : ($response['themes'] ?? []);

        // WordPress API returns info array with total pages
        $info = $response['info'] ?? [];
        $totalPages = (int)($info['pages'] ?? 1);
        $totalResults = (int)($info['results'] ?? count($items));

        return [
            'items' => array_map([$this, 'mapItem'], $items),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalResults,
                'total_pages' => $totalPages,
            ],
        ];
    }

    /**
     * Map WordPress.org API response to standard format
     */
    protected function mapItem(array $item): array
    {
        if ($this->type === 'plugin') {
            return [
                'slug' => $item['slug'] ?? '',
                'name' => $item['name'] ?? 'Untitled',
                'version' => $item['version'] ?? '1.0.0',
                'type' => 'plugin',
                'author' => [
                    'name' => is_array($item['author'] ?? null) 
                        ? ($item['author']['name'] ?? 'Unknown')
                        : ($item['author'] ?? 'Unknown'),
                    'url' => $item['author_profile'] ?? '#',
                ],
                'description' => $item['short_description'] ?? '',
                'download_url' => $item['download_link'] ?? '',
                'screenshot' => $item['icons']['2x'] ?? $item['icons']['1x'] ?? '',
                'icons' => $item['icons'] ?? [],
                'rating' => $item['rating'] ?? 0,
                'num_ratings' => $item['num_ratings'] ?? 0,
                'active_installs' => $item['active_installs'] ?? 0,
                'last_updated' => $item['last_updated'] ?? '',
                'requires' => $item['requires'] ?? '',
                'tested' => $item['tested'] ?? '',
                'stats' => [
                    'rating' => ($item['rating'] ?? 0) / 20, // Convert to 0-5 scale
                    'installs' => $item['active_installs'] ?? 0,
                    'num_ratings' => $item['num_ratings'] ?? 0,
                ],
            ];
        } else {
            // Theme mapping
            return [
                'slug' => $item['slug'] ?? '',
                'name' => $item['name'] ?? 'Untitled',
                'version' => $item['version'] ?? '1.0.0',
                'type' => 'theme',
                'author' => [
                    'name' => is_array($item['author'] ?? null)
                        ? ($item['author']['name'] ?? 'Unknown')
                        : ($item['author'] ?? 'Unknown'),
                    'url' => $item['author_and_uri'] ?? '#',
                ],
                'description' => $item['description'] ?? '',
                'download_url' => $item['download_link'] ?? '',
                'screenshot' => $item['screenshot_url'] ?? '',
                'preview_url' => $item['preview_url'] ?? '',
                'rating' => $item['rating'] ?? 0,
                'num_ratings' => $item['num_ratings'] ?? 0,
                'active_installs' => $item['active_installs'] ?? 0,
                'last_updated' => $item['last_updated_time'] ?? '',
                'stats' => [
                    'rating' => ($item['rating'] ?? 0) / 20,
                    'installs' => $item['active_installs'] ?? 0,
                    'num_ratings' => $item['num_ratings'] ?? 0,
                ],
            ];
        }
    }

    public function findBySlug(string $slug, string $type = 'any'): ?\Prestoworld\MarketplaceSdk\Contracts\RepositoryItemInterface
    {
        // WordPress.org API doesn't have direct slug lookup in this provider
        return null;
    }

    public function count(array $filters = []): int
    {
        $filters['page'] = 1;
        $filters['per_page'] = 1;
        $items = $this->fetchAll($filters);
        return count($items); // WordPress.org API doesn't return total count
    }

    protected function makeRequest(string $url): array
    {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: WordPress/6.0',
                    'Accept: application/json',
                ],
                'timeout' => 30,
            ]
        ];

        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            error_log('[WordPressOrg] API request failed: ' . $url);
            return [];
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[WordPressOrg] JSON parse error: ' . json_last_error_msg());
            return [];
        }

        return $data ?? [];
    }
}
