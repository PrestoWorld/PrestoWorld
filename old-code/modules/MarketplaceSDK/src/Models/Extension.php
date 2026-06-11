<?php

namespace Modules\MarketplaceSDK\Models;

/**
 * Class Extension
 * 
 * Standard Data Object (DTO) for PrestoWorld Hub extensions.
 */
class Extension
{
    public string $slug;
    public string $type; // theme or plugin
    public string $name;
    public string $version;
    public array  $author;
    public string $description;
    public string $download_url;
    public string $screenshot;
    public array  $metadata;

    public function __construct(array $data)
    {
        $this->slug        = $data['slug'] ?? '';
        $this->type        = $data['type'] ?? 'plugin';
        $this->name        = $data['name'] ?? 'Untitled';
        $this->version     = $data['version'] ?? '1.0.0';
        $this->author      = $data['author'] ?? ['name' => 'Unknown', 'url' => '#'];
        $this->description = $data['description'] ?? '';
        $this->download_url = $data['download_url'] ?? '';
        $this->screenshot  = $data['screenshot'] ?? '';
        $this->metadata    = $data['metadata'] ?? [];
    }

    /**
     * Map the DTO to the PrestoWorld JSON API Spec.
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->slug,
            'slug'         => $this->slug,
            'type'         => $this->type,
            'name'         => $this->name,
            'version'      => $this->version,
            'author'       => $this->author,
            'description'  => $this->description,
            'download_url' => $this->download_url,
            'screenshot'   => $this->screenshot,
            'metadata'     => $this->metadata,
        ];
    }
}
