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
        return Response::json(['success' => true, 'message' => 'Use verify API for automatic activation']);
    }

    public function deactivate(Request $request, int $id): Response
    {
        $this->dbal->database()->update('optilarity_licenses', [
            'activated_domains' => '[]',
            'activations_used' => 0
        ], ['id' => $id])->run();
        return Response::json(['success' => true, 'message' => 'All activations cleared']);
    }

    public function revoke(Request $request, int $id): Response
    {
        $this->dbal->database()->update('optilarity_licenses', ['status' => 'revoked'], ['id' => $id])->run();
        return Response::json(['success' => true, 'message' => 'Revoked']);
    }

    /**
     * Secure verification endpoint
     */
    public function verify(Request $request): Response
    {
        $service = app(\Modules\LicenseManager\Services\LicenseService::class);
        $data = (array)$request->post();
        
        // Identification is needed to find the specific private key
        $key = $data['license_key'] ?? $request->query()['key'] ?? '';

        // Support both plain and encrypted payloads
        if (isset($data['payload']) && $key) {
            $decrypted = $service->decryptData($data['payload'], $key);
            if ($decrypted) {
                $data = $decrypted;
            }
        }

        $email = $data['email'] ?? $request->query()['email'] ?? '';
        $domain = $data['domain'] ?? $request->query()['domain'] ?? '';
        $machineId = $data['machine_id'] ?? $data['device_id'] ?? $request->query()['machine_id'] ?? '';
        $version = $data['version'] ?? $request->query()['version'] ?? '';

        // Determine activation identity (domain for web, machine_id for desktop)
        $identity = $machineId ?: $domain;

        $meta = [
            'ip' => $request->server()['REMOTE_ADDR'] ?? null,
            'user_agent' => $request->server()['HTTP_USER_AGENT'] ?? null,
        ];

        $result = $service->verify($key, $email, $identity, $version, $meta);
        
        // Get the specific public key for this license to return it
        $publicKey = '';
        if ($key) {
            $private = $service->getLicensePrivateKey($key);
            if ($private) {
                $publicKey = $service->getPublicKeyFromPrivate($private);
            }
        }

        return Response::json([
            'success' => true,
            'payload' => $key ? $service->encryptResponse($result, $key) : null,
            'public_key' => $publicKey,
            'timestamp' => time()
        ]);
    }
}
