<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Controller;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\Controllers\AbstractController;
use PrestoWorld\Modules\HeadlessCMS\Serializer\PostSerializer;
use PrestoWorld\Modules\HeadlessCMS\Transformer\PostTransformer;

class SearchController extends AbstractController
{
    protected PostSerializer $postSerializer;
    protected PostTransformer $postTransformer;

    public function __construct(
        PostSerializer $postSerializer,
        PostTransformer $postTransformer
    ) {
        $this->postSerializer = $postSerializer;
        $this->postTransformer = $postTransformer;
    }

    /**
     * GET /api/v1/search - Search content
     */
    public function search(Request $request): Response
    {
        $query = $request->query('q') ?? '';
        
        if (!$query) {
            return Response::json([
                'success' => false,
                'error' => 'Search query is required.',
            ], 400);
        }

        $postType = $request->query('post_type');
        $perPage = (int) ($request->query('per_page') ?? 10);
        $page = (int) ($request->query('page') ?? 1);

        // Query posts
        $posts = [];
        $total = 0;

        if ($postType) {
            $posts = (new \PrestoWorld\Modules\Schema\PostRepository(
                new \Cycle\Database\DatabaseInterface()
            ))->find([
                'post_type' => $postType,
                'status' => 'publish',
                'search' => $query,
            ]);
        } else {
            $posts = (new \PrestoWorld\Modules\Schema\PostRepository(
                new \Cycle\Database\DatabaseInterface()
            ))->find([
                'status' => 'publish',
                'search' => $query,
            ]);
        }

        $total = count($posts); // Simplified

        $serializer = new PostSerializer();
        $serialized = $serializer->serializeCollection(array_values($posts));

        $transformed = $this->postTransformer->transformRelatedCollection($serialized);

        return Response::json([
            'success' => true,
            'data' => $transformed,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }
}