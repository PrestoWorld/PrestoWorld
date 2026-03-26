<?php

namespace App\Foundation\Marketplace\Contracts;

/**
 * Interface RepositoryItemInterface
 * 
 * Defines a single item (plugin/theme) in the repository metadata.
 */
interface RepositoryItemInterface
{
    public function getSlug(): string;
    public function getName(): string;
    public function getVersion(): string;
    public function getType(): string; // plugin or theme
    public function getAuthor(): array;
    public function getDescription(): string;
    public function getDownloadUrl(): string;
    public function getScreenshotUrl(): string;
    public function getMetadata(): array;
    
    /**
     * Resolve the full metadata array as specified by PrestoWorld API v1.
     */
    public function resolve(): array;
}
