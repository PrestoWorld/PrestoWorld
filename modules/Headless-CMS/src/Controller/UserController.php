<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Controller;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\Controllers\AbstractController;
use PrestoWorld\Modules\HeadlessCMS\Serializer\UserSerializer;
use PrestoWorld\Modules\HeadlessCMS\Transformer\UserTransformer;

class UserController extends AbstractController
{
    protected UserSerializer $userSerializer;
    protected UserTransformer $userTransformer;

    public function __construct(
        UserSerializer $userSerializer,
        UserTransformer $userTransformer
    ) {
        $this->userSerializer = $userSerializer;
        $this->userTransformer = $userTransformer;
    }

    /**
     * GET /api/v1/users - List users
     */
    public function index(Request $request): Response
    {
        $serializer = new UserSerializer();
        
        // In production, this would fetch from database
        $serialized = $serializer->serializeCollection([
            [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'display_name' => 'Administrator',
                'role' => 'admin',
                'avatar_url' => '/avatars/admin.jpg',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ]);

        return Response::json([
            'success' => true,
            'data' => $serialized,
        ]);
    }

    /**
     * GET /api/v1/users/{id} - Get single user
     */
    public function show(Request $request, int $id): Response
    {
        $serializer = new UserSerializer();
        
        $user = [
            'id' => $id,
            'username' => 'user' . $id,
            'email' => 'user' . $id . '@example.com',
            'display_name' => 'User ' . $id,
            'role' => 'editor',
            'avatar_url' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $serialized = $serializer->serialize($user);

        return Response::json([
            'success' => true,
            'data' => $this->userTransformer->transform($serialized),
        ]);
    }
}