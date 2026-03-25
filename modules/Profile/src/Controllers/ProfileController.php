<?php

declare(strict_types=1);

namespace Modules\Profile\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;

class ProfileController
{
    protected DatabaseProviderInterface $dbal;

    public function __construct(mixed $app)
    {
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    public function show(Request $request): Response
    {
        $userId = $request->query()['user_id'] ?? 1;
        $profile = $this->dbal->database()->select('*')->from('presto_profiles')->where('user_id', $userId)->run()->fetch();
        if (!$profile) return Response::json(['error' => 'Profile not found'], 404);
        return Response::json($profile);
    }

    public function update(Request $request): Response
    {
        $data = (array)$request->post();
        $userId = $data['user_id'] ?? 1;
        $this->dbal->database()->update('presto_profiles', array_merge($data, [
            'updated_at' => date('Y-m-d H:i:s')
        ]), ['user_id' => $userId]);
        return Response::json(['success' => true]);
    }

    public function updateAvatar(Request $request): Response
    {
        return Response::json(['success' => true, 'message' => 'Avatar updated']);
    }

    public function showUser(Request $request, int $userId): Response
    {
        $profile = $this->dbal->database()->select('*')->from('presto_profiles')->where('user_id', $userId)->run()->fetch();
        if (!$profile) return Response::json(['error' => 'Profile not found'], 404);
        return Response::json($profile);
    }
}
