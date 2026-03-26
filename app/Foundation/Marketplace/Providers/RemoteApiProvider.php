<?php

namespace App\Foundation\Marketplace\Providers;

use App\Foundation\Marketplace\Contracts\RepositoryProviderInterface;
use App\Foundation\Marketplace\Contracts\RepositoryItemInterface;

/**
 * Class RemoteApiProvider
 * 
 * Fetches plugins and themes from a remote PrestoWorld Hub URL.
 */
class RemoteApiProvider implements RepositoryProviderInterface
{
    protected string $url;
    protected string $token;

    public function __construct(string $url, string $token = '')
    {
        $this->url = rtrim($url, '/');
        $this->token = $token;
    }

    public function getProviderId(): string
    {
        return parse_url($this->url, PHP_URL_HOST) ?? 'unknown-hub';
    }

    /**
     * Fetch all items from the remote Hub.
     */
    public function fetchAll(array $filters = []): array
    {
        $query = http_build_query($filters);
        $endpoint = "{$this->url}/catalog?{$query}";
        
        $response = $this->makeRequest($endpoint);
        
        if (!isset($response['items']) || !is_array($response['items'])) {
            return [];
        }

        return array_map(function ($item) {
            // Map the HUB-spec data to a standard PrestoWorld item structure
            return $item;
        }, $response['items']);
    }

    /**
     * Resolve a single item by slug.
     */
    public function findBySlug(string $slug, string $type = 'any'): ?RepositoryItemInterface
    {
        $endpoint = "{$this->url}/item/{$slug}?type={$type}";
        $response = $this->makeRequest($endpoint);
        
        if (isset($response['error'])) {
            return null;
        }

        return new RemoteRepositoryItem($response);
    }

    public function count(array $filters = []): int
    {
        $query = http_build_query($filters);
        $endpoint = "{$this->url}/catalog?{$query}";
        $response = $this->makeRequest($endpoint);
        
        return $response['pagination']['total'] ?? count($response['items'] ?? []);
    }

    /**
     * Reference implementation of a Secure/Cached HTTP GET.
     */
    protected function makeRequest(string $url): array
    {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PrestoWorld/' . app('version'),
                    'Accept: application/json',
                ]
            ]
        ];

        if ($this->token) {
            $opts['http']['header'][] = "Authorization: Bearer {$this->token}";
        }

        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new \Exception("Could not connect to Hub " . $this->getProviderId());
        }

        return json_decode($response, true) ?? ['error' => 'Invalid JSON'];
    }
}

/**
 * Class RemoteRepositoryItem
 * Wrapper for the fetched data.
 */
class RemoteRepositoryItem implements RepositoryItemInterface
{
    protected array $data;
    
    public function __construct(array $data) { $this->data = $data; }

    public function getSlug(): string { return $this->data['slug'] ?? ''; }
    public function getName(): string { return $this->data['name'] ?? 'Untitled'; }
    public function getVersion(): string { return $this->data['version'] ?? '1.0.0'; }
    public function getType(): string { return $this->data['type'] ?? 'plugin'; }
    public function getAuthor(): array { return $this->data['author'] ?? ['name' => 'Unknown', 'url' => '#']; }
    public function getDescription(): string { return $this->data['description'] ?? ''; }
    public function getDownloadUrl(): string { return $this->data['download_url'] ?? ''; }
    public function getScreenshotUrl(): string { return $this->data['screenshot'] ?? ''; }
    public function getMetadata(): array { return $this->data; }
    
    public function resolve(): array {
        return array_merge($this->data, [
            'id' => $this->getSlug(),
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'author' => $this->getAuthor(),
            'description' => $this->getDescription(),
            'download_url' => $this->getDownloadUrl(),
            'screenshot_url' => $this->getScreenshotUrl()
        ]);
    }
}
