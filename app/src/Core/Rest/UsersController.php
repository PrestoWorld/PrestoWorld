<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Rest;

use PrestoWorld\Core\User\UserEntity;
use PrestoWorld\Core\UserRepository;

/**
 * UsersController — replaces WP_REST_Users_Controller.
 */
class UsersController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->namespace = 'prestoworld/v1';
        $this->rest_base = 'users';
    }

    public function get_items(mixed $request): mixed
    {
        $args = [];
        if ($request instanceof RestRequest) {
            foreach ($request->get_params() as $key => $value) {
                if (is_string($key)) {
                    $args[$key] = $value;
                }
            }
        } elseif (is_array($request)) {
            foreach ($request as $key => $value) {
                if (is_string($key)) {
                    $args[$key] = $value;
                }
            }
        }

        $result = [];
        foreach (UserRepository::query($args) as $user) {
            $result[] = $this->prepare_item_for_response($user, $request);
        }

        return $result;
    }

    public function get_item(mixed $request): mixed
    {
        $id = null;
        if ($request instanceof RestRequest) {
            $id = $request->get_param('id');
        } elseif (is_array($request)) {
            $id = $request['id'] ?? null;
        }

        $userId = is_numeric($id) ? (int) $id : 0;
        if ($userId <= 0) {
            return null;
        }

        $user = UserRepository::find($userId);
        if ($user === false) {
            return null;
        }

        return $this->prepare_item_for_response($user, $request);
    }

    public function prepare_item_for_response(mixed $item, mixed $request): mixed
    {
        if ($item instanceof UserEntity) {
            return $item->to_array();
        }

        if (is_array($item)) {
            return $item;
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_item_schema(): array
    {
        return [
            'name' => ['type' => 'string'],
            'email' => ['type' => 'string'],
            'roles' => ['type' => 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function get_collection_params(): array
    {
        return [
            'page' => ['type' => 'integer'],
            'per_page' => ['type' => 'integer'],
            'search' => ['type' => 'string'],
            'role' => ['type' => 'string'],
        ];
    }
}
