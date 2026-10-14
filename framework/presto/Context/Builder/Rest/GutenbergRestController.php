<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Rest;

use PrestoWorld\Core\Rest\BaseController;
use PrestoWorld\Modules\ContextBuilder\Gutenberg\GutenbergIntegration;

/**
 * GutenbergRestController — REST API endpoints for Gutenberg fork.
 *
 * Provides the endpoints that Gutenberg editor needs:
 *   - /pw-api/v1/media — media library
 *   - /pw-api/v1/media/upload — upload media
 *   - /pw-api/v1/templates — available templates
 *   - /pw-api/v1/template-parts — available template parts
 *   - /pw-api/v1/block-patterns — available block patterns
 *   - /pw-api/v1/settings — editor settings
 */
class GutenbergRestController extends BaseController
{
    public string $namespace = 'pw-api/v1';

    public string $rest_base = '';

    public function register_routes(): void
    {
        // GET /pw-api/v1/settings — editor settings
        register_rest_route($this->namespace, '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // GET /pw-api/v1/media — list media
        register_rest_route($this->namespace, '/media', [
            'methods' => 'GET',
            'callback' => [$this, 'get_media'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // POST /pw-api/v1/media/upload — upload media
        register_rest_route($this->namespace, '/media/upload', [
            'methods' => 'POST',
            'callback' => [$this, 'upload_media'],
            'permission_callback' => [$this, 'create_item_permissions_check'],
        ]);

        // GET /pw-api/v1/templates — list templates
        register_rest_route($this->namespace, '/templates', [
            'methods' => 'GET',
            'callback' => [$this, 'get_templates'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // GET /pw-api/v1/templates/<slug> — get template content
        register_rest_route($this->namespace, '/templates/(?P<slug>[\w-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_template'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // PUT /pw-api/v1/templates/<slug> — save template
        register_rest_route($this->namespace, '/templates/(?P<slug>[\w-]+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'save_template'],
            'permission_callback' => [$this, 'create_item_permissions_check'],
        ]);

        // GET /pw-api/v1/template-parts — list template parts
        register_rest_route($this->namespace, '/template-parts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_template_parts'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // GET /pw-api/v1/block-patterns — list block patterns
        register_rest_route($this->namespace, '/block-patterns', [
            'methods' => 'GET',
            'callback' => [$this, 'get_block_patterns'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // GET /pw-api/v1/block-types — list block types
        register_rest_route($this->namespace, '/block-types', [
            'methods' => 'GET',
            'callback' => [$this, 'get_block_types'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // GET /pw-api/v1/contexts — list context types
        register_rest_route($this->namespace, '/contexts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_contexts'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // GET /pw-api/v1/contexts/<type>/layout — get context layout
        register_rest_route($this->namespace, '/contexts/(?P<type>[\w-]+)/layout', [
            'methods' => 'GET',
            'callback' => [$this, 'get_context_layout'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // PUT /pw-api/v1/contexts/<type>/layout — save context layout
        register_rest_route($this->namespace, '/contexts/(?P<type>[\w-]+)/layout', [
            'methods' => 'PUT',
            'callback' => [$this, 'save_context_layout'],
            'permission_callback' => [$this, 'create_item_permissions_check'],
        ]);
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_settings($request): array
    {
        return [
            'success' => true,
            'settings' => GutenbergIntegration::getEditorConfig(),
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_media($request): array
    {
        // In real implementation, this would query MediaService
        return [
            'success' => true,
            'media' => [],
            'total' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function upload_media($request): array
    {
        // In real implementation, this would handle file upload
        return [
            'success' => false,
            'error' => 'Media upload not implemented yet',
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_templates($request): array
    {
        $themeDir = \PrestoWorld\Core\ThemeManager::stylesheetDirectory();
        $templatesDir = $themeDir . '/templates';

        $templates = [];
        if (is_dir($templatesDir)) {
            $files = scandir($templatesDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                if (pathinfo($file, PATHINFO_EXTENSION) === 'html') {
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $templates[] = [
                        'slug' => $name,
                        'title' => ucwords(str_replace('-', ' ', $name)),
                        'file' => $file,
                        'content' => file_get_contents($templatesDir . '/' . $file),
                    ];
                }
            }
        }

        return [
            'success' => true,
            'templates' => $templates,
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_template($request): array
    {
        $slug = (string) ($request['slug'] ?? '');
        $themeDir = \PrestoWorld\Core\ThemeManager::stylesheetDirectory();
        $templatePath = $themeDir . '/templates/' . $slug . '.html';

        if (!is_file($templatePath)) {
            return [
                'success' => false,
                'error' => 'Template not found: ' . $slug,
            ];
        }

        $content = file_get_contents($templatePath);

        return [
            'success' => true,
            'slug' => $slug,
            'content' => $content ?: '',
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function save_template($request): array
    {
        $slug = (string) ($request['slug'] ?? '');
        $content = (string) ($request['content'] ?? '');

        // Save to storage (user-edited templates go to storage)
        $storageDir = \PrestoWorld\Core\Path::storagePath('contexts');
        $templatePath = $storageDir . '/templates/' . $slug . '.html';

        if (!is_dir(dirname($templatePath))) {
            mkdir(dirname($templatePath), 0755, true);
        }

        $saved = file_put_contents($templatePath, $content) !== false;

        return [
            'success' => $saved,
            'slug' => $slug,
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_template_parts($request): array
    {
        $themeDir = \PrestoWorld\Core\ThemeManager::stylesheetDirectory();
        $partsDir = $themeDir . '/parts';

        $parts = [];
        if (is_dir($partsDir)) {
            $files = scandir($partsDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                if (pathinfo($file, PATHINFO_EXTENSION) === 'html') {
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $parts[] = [
                        'slug' => $name,
                        'title' => ucwords(str_replace('-', ' ', $name)),
                        'content' => file_get_contents($partsDir . '/' . $file),
                    ];
                }
            }
        }

        return [
            'success' => true,
            'template_parts' => $parts,
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_block_patterns($request): array
    {
        // In real implementation, this would scan patterns directory
        return [
            'success' => true,
            'patterns' => [],
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_block_types($request): array
    {
        // Get all registered blocks
        $blockRegistry = \PrestoWorld\Modules\ContextBuilder\BlockRegistry::class;
        // In real implementation, this would use the DI container
        return [
            'success' => true,
            'block_types' => [],
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_contexts($request): array
    {
        // Get all context types
        return [
            'success' => true,
            'contexts' => [],
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function get_context_layout($request): array
    {
        $type = (string) ($request['type'] ?? '');

        return [
            'success' => true,
            'context_type' => $type,
            'layout' => [],
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function save_context_layout($request): array
    {
        $type = (string) ($request['type'] ?? '');
        $layout = $request['layout'] ?? [];

        return [
            'success' => true,
            'context_type' => $type,
        ];
    }
}