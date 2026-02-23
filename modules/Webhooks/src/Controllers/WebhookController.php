<?php

declare(strict_types=1);

namespace Modules\Webhooks\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;

class WebhookController
{
    protected mixed $app;
    protected DatabaseProviderInterface $dbal;

    public function __construct(mixed $app)
    {
        $this->app = $app;
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    public function index(Request $request): Response
    {
        $webhooks = $this->dbal->database()->select('*')->from('optilarity_webhooks')->fetchAll();
        return Response::json($webhooks);
    }

    public function show(Request $request, int $id): Response
    {
        $webhook = $this->dbal->database()->select('*')->from('optilarity_webhooks')->where('id', $id)->run()->fetch();
        if (!$webhook) {
            return Response::json(['error' => 'Webhook not found'], 404);
        }
        return Response::json($webhook);
    }

    public function store(Request $request): Response
    {
        $data = (array)$request->post();
        $id = $this->dbal->database()->insert('optilarity_webhooks')->values([
            'name'       => $data['name'] ?? '',
            'url'        => $data['url'] ?? '',
            'events'     => json_encode($data['events'] ?? []),
            'is_active'  => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
        ])->run();

        return Response::json(['id' => $id, 'success' => true]);
    }

    public function update(Request $request, int $id): Response
    {
        $data = (array)$request->post();
        $this->dbal->database()->update('optilarity_webhooks', [
            'name'       => $data['name'] ?? '',
            'url'        => $data['url'] ?? '',
            'events'     => json_encode($data['events'] ?? []),
            'is_active'  => $data['is_active'] ?? 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        return Response::json(['success' => true]);
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->dbal->database()->delete('optilarity_webhooks')->where('id', $id)->run();
        return Response::json(['success' => true]);
    }

    public function deliveries(Request $request, int $id): Response
    {
        $deliveries = $this->dbal->database()->select('*')
            ->from('optilarity_webhook_deliveries')
            ->where('webhook_id', $id)
            ->orderBy('created_at', 'DESC')
            ->fetchAll();

        return Response::json($deliveries);
    }
}
