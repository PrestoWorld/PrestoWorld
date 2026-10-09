<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Rest;

use PrestoWorld\Core\Rest\BaseController;
use PrestoWorld\Modules\ContextBuilder\ContextBuilder;

/**
 * ContextBuilderController — REST API cho Gutenberg fork.
 *
 * Gutenberg fork gọi /pw-api/v1/ để:
 *   - Lấy layout hiện tại (full site edit)
 *   - Lưu layout đã edit (flat file .html trong storage)
 *   - Lấy content để edit (content edit)
 *   - Lấy danh sách blocks, context types
 */
class ContextBuilderController extends BaseController
{
    public string $namespace = 'pw-api/v1';

    public string $rest_base = 'context-builder';

    protected ContextBuilder $builder;

    public function __construct(ContextBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function register_routes(): void
    {
        // GET /pw-api/v1/context-builder/context-types
        // Danh sách context types (taxonomy, post, page, custom)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/context-types', [
            'methods' => 'GET',
            'callback' => [$this, 'get_context_types'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // GET /pw-api/v1/context-builder/blocks
        // Danh sách blocks đã đăng ký
        register_rest_route($this->namespace, '/' . $this->rest_base . '/blocks', [
            'methods' => 'GET',
            'callback' => [$this, 'get_blocks'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // GET /pw-api/v1/context-builder/layout/<context_type>
        // Lấy layout hiện tại (full site edit)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/layout/(?P<context_type>[\w-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_layout'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // POST /pw-api/v1/context-builder/layout/<context_type>
        // Lưu layout đã edit ra flat file .html trong storage
        register_rest_route($this->namespace, '/' . $this->rest_base . '/layout/(?P<context_type>[\w-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'save_layout'],
            'permission_callback' => [$this, 'create_item_permissions_check'],
        ]);

        // GET /pw-api/v1/context-builder/content/<context_type>/<id>
        // Lấy content để edit (content edit)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/content/(?P<context_type>[\w-]+)/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_content'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // POST /pw-api/v1/context-builder/content/<context_type>/<id>
        // Lưu content đã edit
        register_rest_route($this->namespace, '/' . $this->rest_base . '/content/(?P<context_type>[\w-]+)/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'save_content'],
            'permission_callback' => [$this, 'create_item_permissions_check'],
        ]);

        // POST /pw-api/v1/context-builder/render/<context_type>
        // Render context ra HTML (hybrid SSR/CSR)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/render/(?P<context_type>[\w-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'render'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_context_types($request): array
    {
        return [
            'success' => true,
            'context_types' => $this->builder->getContextTypes(),
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_blocks($request): array
    {
        return [
            'success' => true,
            'blocks' => $this->builder->getBlockDefinitions(),
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_layout($request): array
    {
        $contextType = (string) ($request['context_type'] ?? '');
        $layout = $this->builder->getLayout($contextType);

        return [
            'success' => true,
            'context_type' => $contextType,
            'layout' => $layout,
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function save_layout($request): array
    {
        $contextType = (string) ($request['context_type'] ?? '');
        $layout = $request['layout'] ?? [];

        if (!is_array($layout)) {
            return [
                'success' => false,
                'error' => 'Layout must be an array',
            ];
        }

        $saved = $this->builder->saveLayout($contextType, $layout);

        return [
            'success' => $saved,
            'context_type' => $contextType,
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_content($request): array
    {
        $contextType = (string) ($request['context_type'] ?? '');
        $id = (int) ($request['id'] ?? 0);

        $content = $this->builder->getContent($contextType, $id);

        return [
            'success' => true,
            'context_type' => $contextType,
            'id' => $id,
            'content' => $content,
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function save_content($request): array
    {
        $contextType = (string) ($request['context_type'] ?? '');
        $id = (int) ($request['id'] ?? 0);
        $content = $request['content'] ?? [];

        if (!is_array($content)) {
            return [
                'success' => false,
                'error' => 'Content must be an array',
            ];
        }

        $saved = $this->builder->saveContent($contextType, $id, $content);

        return [
            'success' => $saved,
            'context_type' => $contextType,
            'id' => $id,
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function render($request): array
    {
        $contextType = (string) ($request['context_type'] ?? '');
        $data = $request['data'] ?? [];

        if (!is_array($data)) {
            $data = [];
        }

        $html = $this->builder->render($contextType, $data);

        return [
            'success' => true,
            'context_type' => $contextType,
            'html' => $html,
        ];
    }
}
