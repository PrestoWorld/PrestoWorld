<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Rest;

use PrestoWorld\Core\MetaRepository;

/**
 * MetaFieldsController — replaces WP_REST_Meta_Fields.
 */
class MetaFieldsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->namespace = 'prestoworld/v1';
        $this->rest_base = 'meta';
    }

    public function get_item(mixed $request): mixed
    {
        [$type, $objectId, $key] = $this->extract($request);
        if ($objectId <= 0 || $key === '') {
            return null;
        }

        return MetaRepository::get($type, $objectId, $key, true);
    }

    public function update_item(mixed $request): mixed
    {
        [$type, $objectId, $key] = $this->extract($request);
        if ($objectId <= 0 || $key === '') {
            return null;
        }

        $value = null;
        if ($request instanceof RestRequest) {
            $value = $request->get_param('value');
        } elseif (is_array($request)) {
            $value = $request['value'] ?? null;
        }

        MetaRepository::update($type, $objectId, $key, $value);

        return MetaRepository::get($type, $objectId, $key, true);
    }

    public function delete_item(mixed $request): mixed
    {
        [$type, $objectId, $key] = $this->extract($request);
        if ($objectId <= 0 || $key === '') {
            return null;
        }

        return MetaRepository::delete($type, $objectId, $key);
    }

    /**
     * @return array<string, mixed>
     */
    public function get_item_schema(): array
    {
        return [
            'meta_type' => ['type' => 'string'],
            'meta_key' => ['type' => 'string'],
            'meta_value' => ['type' => 'object'],
        ];
    }

    /**
     * @return array{0: string, 1: int, 2: string}
     */
    private function extract(mixed $request): array
    {
        $type = 'post';
        $objectId = 0;
        $key = '';

        $params = [];
        if ($request instanceof RestRequest) {
            $params = $request->get_params();
        } elseif (is_array($request)) {
            $params = $request;
        }

        if (isset($params['meta_type']) && is_string($params['meta_type'])) {
            $type = $params['meta_type'];
        }
        if (isset($params['object_id']) && is_numeric($params['object_id'])) {
            $objectId = (int) $params['object_id'];
        }
        if (isset($params['meta_key']) && is_string($params['meta_key'])) {
            $key = $params['meta_key'];
        }

        return [$type, $objectId, $key];
    }
}
