<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Controller;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\Controllers\AbstractController;
use PrestoWorld\Modules\HeadlessCMS\Serializer\MenuSerializer;
use PrestoWorld\Modules\HeadlessCMS\Transformer\MenuTransformer;

class MenuController extends AbstractController
{
    protected MenuSerializer $menuSerializer;
    protected MenuTransformer $menuTransformer;

    public function __construct(
        MenuSerializer $menuSerializer,
        MenuTransformer $menuTransformer
    ) {
        $this->menuSerializer = $menuSerializer;
        $this->menuTransformer = $menuTransformer;
    }

    /**
     * GET /api/v1/menus - List all menus
     */
    public function index(Request $request): Response
    {
        // In production, this would query the database
        // For now, return empty structure
        $serializer = new MenuSerializer();
        $serialized = $serializer->serializeCollection([]);

        return Response::json([
            'success' => true,
            'data' => $serialized,
        ]);
    }

    /**
     * GET /api/v1/menus/{slug} - Get menu by slug
     */
    public function show(Request $request, string $slug): Response
    {
        // In production, this would fetch the specific menu from database
        $serializer = new MenuSerializer();
        
        return Response::json([
            'success' => true,
            'data' => [
                'id' => 1,
                'name' => 'Main Menu',
                'slug' => $slug,
                'items': [],
            ],
        ]);
    }
}