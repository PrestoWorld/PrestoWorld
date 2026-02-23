<?php

declare(strict_types=1);

namespace Modules\LicenseManager\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;

class LicenseController
{
    protected DatabaseProviderInterface $dbal;

    public function __construct(mixed $app)
    {
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    public function index(Request $request): Response
    {
        $items = $this->dbal->database()->select('*')->from('optilarity_licenses')->fetchAll();
        return Response::json($items);
    }

    public function show(Request $request, int $id): Response
    {
        $item = $this->dbal->database()->select('*')->from('optilarity_licenses')->where('id', $id)->run()->fetch();
        if (!$item) return Response::json(['error' => 'Not found'], 404);
        return Response::json($item);
    }

    public function store(Request $request): Response
    {
        $data = (array)$request->post();
        $id = $this->dbal->database()->insert('optilarity_licenses')->values(array_merge($data, [
            'created_at' => date('Y-m-d H:i:s')
        ]))->run();
        return Response::json(['id' => $id, 'success' => true]);
    }

    public function update(Request $request, int $id): Response
    {
        $data = (array)$request->post();
        $this->dbal->database()->update('optilarity_licenses', array_merge($data, [
            'updated_at' => date('Y-m-d H:i:s')
        ]), ['id' => $id]);
        return Response::json(['success' => true]);
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->dbal->database()->delete('optilarity_licenses')->where('id', $id)->run();
        return Response::json(['success' => true]);
    }

    public function activate(Request $request, int $id): Response
    {
        return Response::json(['success' => true, 'message' => 'Activated']);
    }

    public function deactivate(Request $request, int $id): Response
    {
        return Response::json(['success' => true, 'message' => 'Deactivated']);
    }

    public function revoke(Request $request, int $id): Response
    {
        $this->dbal->database()->update('optilarity_licenses', ['status' => 'revoked'], ['id' => $id]);
        return Response::json(['success' => true, 'message' => 'Revoked']);
    }

    public function verify(Request $request): Response
    {
        $key = $request->query()['key'] ?? '';
        $license = $this->dbal->database()->select('*')->from('optilarity_licenses')->where('license_key', $key)->run()->fetch();
        if (!$license) return Response::json(['valid' => false], 404);
        return Response::json(['valid' => true, 'status' => $license['status']]);
    }
}
