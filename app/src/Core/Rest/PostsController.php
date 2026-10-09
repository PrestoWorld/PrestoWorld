<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Rest;

use PrestoWorld\Core\Post\PostEntity;
use PrestoWorld\Core\PostRepository;
use PrestoWorld\Core\PostService;

/**
 * PostsController — replaces WP_REST_Posts_Controller.
 */
class PostsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->namespace = 'prestoworld/v1';
        $this->rest_base = 'posts';
    }

    public function get_items(mixed $request): mixed
    {
        $args = [];
        if ($request instanceof RestRequest) {
            $params = $request->get_params();
            foreach ($params as $key => $value) {
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

        $posts = PostRepository::query($args);

        $result = [];
        foreach ($posts as $post) {
            $prepared = $this->prepare_item_for_response($post, $request);
            if (is_array($prepared) || is_scalar($prepared) || $prepared instanceof PostEntity) {
                $result[] = $prepared;
            }
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

        $postId = is_numeric($id) ? (int) $id : 0;
        if ($postId <= 0) {
            return null;
        }

        $post = PostRepository::find($postId);
        if ($post === null) {
            return null;
        }

        return $this->prepare_item_for_response($post, $request);
    }

    public function create_item(mixed $request): mixed
    {
        $data = [];
        if ($request instanceof RestRequest) {
            $params = $request->get_params();
            foreach ($params as $key => $value) {
                if (is_string($key)) {
                    $data[$key] = $value;
                }
            }
            $bodyParams = $request->get_body_params();
            foreach ($bodyParams as $key => $value) {
                if (is_string($key)) {
                    $data[$key] = $value;
                }
            }
            $body = $request->get_body();
            if (is_array($body)) {
                foreach ($body as $key => $value) {
                    if (is_string($key)) {
                        $data[$key] = $value;
                    }
                }
            }
        } elseif (is_array($request)) {
            foreach ($request as $key => $value) {
                if (is_string($key)) {
                    $data[$key] = $value;
                }
            }
        }

        $postId = PostService::create($data);
        if ($postId <= 0) {
            return null;
        }

        $post = PostRepository::find($postId);
        if ($post === null) {
            return null;
        }

        return $this->prepare_item_for_response($post, $request);
    }

    public function prepare_item_for_response(mixed $item, mixed $request): mixed
    {
        if ($item instanceof PostEntity) {
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
            'title' => ['type' => 'string'],
            'content' => ['type' => 'string'],
            'status' => ['type' => 'string'],
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
        ];
    }
}
