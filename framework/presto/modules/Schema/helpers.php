<?php

declare(strict_types=1);

use PrestoWorld\Modules\Schema\PostTypeSchemaManager;

if (!function_exists('register_post_type')) {
    /**
     * Compatibility function for WordPress register_post_type
     * 
     * In PrestoWorld, this triggers a Live Migration to ensure
     * a dedicated database table exists for the post type.
     */
    function register_post_type(string $postType, array $args = []): void
    {
        app(PostTypeSchemaManager::class)->register($postType, $args);
    }
}

if (!function_exists('register_post_meta')) {
    /**
     * Register post meta and sync it as a table column
     */
    function register_post_meta(string $postType, string $metaKey, array $args = []): void
    {
        $type = $args['type'] ?? 'string';
        app(PostTypeSchemaManager::class)->registerMeta($postType, $metaKey, $type, $args);
    }
}
