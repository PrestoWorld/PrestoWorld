<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Rest;

use PrestoWorld\Core\Error\PrestoError;

/**
 * BaseController — replaces WP_REST_Controller.
 */
class BaseController
{
    public string $namespace = 'prestoworld/v1';
    public string $rest_base = '';

    public function __construct()
    {
    }

    public function register_routes(): void
    {
    }

    public function get_items(mixed $request): mixed
    {
        return [];
    }

    public function get_item(mixed $request): mixed
    {
        return null;
    }

    public function create_item(mixed $request): mixed
    {
        return null;
    }

    public function update_item(mixed $request): mixed
    {
        return null;
    }

    public function delete_item(mixed $request): mixed
    {
        return null;
    }

    public function prepare_item_for_response(mixed $item, mixed $request): mixed
    {
        return $item;
    }

    public function prepare_response_for_collection(mixed $response): mixed
    {
        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_item_schema(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function get_collection_params(): array
    {
        return [];
    }

    public function get_items_permissions_check(mixed $request): bool|PrestoError
    {
        return true;
    }

    public function create_item_permissions_check(mixed $request): bool|PrestoError
    {
        return true;
    }
}
