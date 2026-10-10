<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Transformer;

use PrestoWorld\Modules\HeadlessCMS\Serializer\UserSerializer;

class UserTransformer
{
    /**
     * Transform user
     */
    public function transform(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'type' => 'users',
            'attributes' => [
                'username' => $user['username'] ?? '',
                'email' => $user['email'] ?? '',
                'display_name' => $user['display_name'] ?? '',
                'role' => $user['role'] ?? 'subscriber',
                'avatar_url' => $user['avatar_url'] ?? null,
                'created_at' => $user['created_at'] ?? null,
            ],
        ];
    }
}