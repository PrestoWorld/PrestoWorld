<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Foundation\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Context Editor data API — /api/admin/context/*.
 *
 * delegate toàn bộ sang ContextBuilder (framework/presto/Context/Builder),
 * không tái implement business logic.
 */
class ContextApiController
{
    public function __construct(protected Application $app)
    {
    }

    /** GET /api/admin/context/types */
    public function types(): Response
    {
        return Response::json([
            'success' => true,
            'types' => $this->builder() !== null ? $this->builder()->getContextTypes() : [],
        ]);
    }

    /** GET /api/admin/context/blocks */
    public function blocks(): Response
    {
        $builder = $this->builder();

        return Response::json([
            'success' => true,
            'blocks' => $builder !== null ? $builder->getBlockDefinitions() : [],
        ]);
    }

    /** GET /api/admin/context/layout/{contextType} */
    public function getLayout(string $contextType): Response
    {
        $builder = $this->builder();

        if ($builder === null) {
            return Response::json(['success' => false, 'error' => 'ContextBuilder unavailable'], 500);
        }

        $layout = $builder->getLayout($contextType);

        return Response::json([
            'success' => true,
            'context_type' => $contextType,
            'layout' => $layout,
            'regions' => $this->regions($contextType),
        ]);
    }

    /** POST /api/admin/context/layout/{contextType}  {layout:{region:[blocks]}} */
    public function saveLayout(Request $request, string $contextType): Response
    {
        $builder = $this->builder();

        if ($builder === null) {
            return Response::json(['success' => false, 'error' => 'ContextBuilder unavailable'], 500);
        }

        $payload = $request->getJson() ?? [];
        $layout = $payload['layout'] ?? null;

        if (!is_array($layout)) {
            return Response::json(['success' => false, 'error' => 'Thiếu field "layout" (object {region:[blocks]})'], 422);
        }

        // Chuẩn hoá: mỗi block phải có type (chuẩn của Loader::renderRegion)
        foreach ($layout as $region => $blocks) {
            if (!is_array($blocks)) {
                return Response::json(['success' => false, 'error' => "Region \"{$region}\" phải là mảng block"], 422);
            }
        }

        $saved = $builder->saveLayout($contextType, $layout);

        return Response::json([
            'success' => $saved,
            'context_type' => $contextType,
            'path' => 'storage/contexts/' . $contextType . '/layout.html',
        ], $saved ? 200 : 500);
    }

    /** POST /api/admin/context/render/{contextType}  {data:{...}} → HTML preview */
    public function render(Request $request, string $contextType): Response
    {
        $builder = $this->builder();

        if ($builder === null) {
            return Response::json(['success' => false, 'error' => 'ContextBuilder unavailable'], 500);
        }

        $payload = $request->getJson() ?? [];
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $html = $builder->render($contextType, $data);

        return Response::json([
            'success' => true,
            'context_type' => $contextType,
            'html' => $html,
        ]);
    }

    /**
     * @return list<string>
     */
    protected function regions(string $contextType): array
    {
        $types = $this->builder()?->getContextTypes() ?? [];
        $type = $types[$contextType] ?? null;

        if (is_array($type) && isset($type['regions']) && is_array($type['regions'])) {
            return array_values(array_map('strval', $type['regions']));
        }

        return ['header', 'content', 'sidebar', 'footer'];
    }

    protected function builder(): ?\PrestoWorld\Modules\ContextBuilder\ContextBuilder
    {
        try {
            if (!$this->app->has(\PrestoWorld\Modules\ContextBuilder\ContextBuilder::class)) {
                return null;
            }

            $builder = $this->app->make(\PrestoWorld\Modules\ContextBuilder\ContextBuilder::class);

            return $builder instanceof \PrestoWorld\Modules\ContextBuilder\ContextBuilder ? $builder : null;
        } catch (\Throwable $e) {
            error_log('[ContextApi] builder resolve failed: ' . $e->getMessage());

            return null;
        }
    }
}
