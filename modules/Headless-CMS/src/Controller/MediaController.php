<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Controller;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\Controllers\AbstractController;
use PrestoWorld\Modules\HeadlessCMS\Serializer\MediaSerializer;
use PrestoWorld\Modules\HeadlessCMS\Transformer\MediaTransformer;

class MediaController extends AbstractController
{
    protected MediaSerializer $mediaSerializer;
    protected MediaTransformer $mediaTransformer;

    public function __construct(
        MediaSerializer $mediaSerializer,
        MediaTransformer $mediaTransformer
    ) {
        $this->mediaSerializer = $mediaSerializer;
        $this->mediaTransformer = $mediaTransformer;
    }

    /**
     * GET /api/v1/media - List media items
     */
    public function index(Request $request): Response
    {
        $query = [];

        $postType = $request->query('post_type');
        if ($postType) {
            $query['post_type'] = $postType;
        }

        $search = $request->query('search');
        if ($search) {
            $query['search'] = $search;
        }

        $perPage = (int) ($request->query('per_page') ?? 20);
        if ($perPage > 0) {
            $query['per_page'] = $perPage;
        }

        $page = (int) ($request->query('page') ?? 1);
        if ($page > 0) {
            $query['offset'] = ($page - 1) * $perPage;
        }

        $serializer = new MediaSerializer();
        $postData = [
            'post_type' => 'attachment',
            'status' => 'publish',
        ];

        // Use PostRepository to query media
        $posts = (new \PrestoWorld\Modules\Schema\PostRepository(new \Cycle\Database\DatabaseInterface()))->find($postData);
        $total = 0; // Simplified

        $serialized = $serializer->serializeCollection(is_array($posts) ? $posts : []);
        
        return Response::json([
            'success' => true,
            'data' => $serialized,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * GET /api/v1/media/{id} - Get single media
     */
    public function show(Request $request, int $id): Response
    {
        $serializer = new MediaSerializer();
        
        // In a real implementation, we'd query the database
        // For now, return a basic structure
        return Response::json([
            'success' => true,
            'data' => [
                'id' => $id,
                'title' => 'Media Item',
                'slug' => 'media-item',
                'file_url' => '/placeholder.jpg',
                'file_path' => '',
                'mime_type' => 'image/jpeg',
                'file_size' => 0,
                'width' => 0,
                'height' => 0,
                'alt_text' => '',
                'caption' => '',
                'description' => '',
            ],
        ]);
    }
}