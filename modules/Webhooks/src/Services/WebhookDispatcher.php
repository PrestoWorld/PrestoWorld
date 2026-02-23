<?php

declare(strict_types=1);

namespace Modules\Webhooks\Services;

use Cycle\Database\DatabaseProviderInterface;

class WebhookDispatcher
{
    private mixed $app;
    private DatabaseProviderInterface $dbal;

    public function __construct(mixed $app)
    {
        $this->app = $app;
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    /**
     * Dispatch a webhook event.
     *
     * @param string $event   Event name (e.g. 'order.created')
     * @param array  $payload Data to send
     */
    public function dispatch(string $event, array $payload): void
    {
        $db = $this->dbal->database();

        // Find active webhooks subscribed to this event
        $webhooks = $db->select('*')
            ->from('optilarity_webhooks')
            ->where('is_active', 1)
            ->fetchAll();

        foreach ($webhooks as $webhook) {
            $events = json_decode($webhook['events'] ?? '[]', true);
            if (!in_array($event, $events, true)) {
                continue;
            }

            $this->logDelivery($webhook, $event, $payload);
        }
    }

    /**
     * Log the delivery attempt.
     * In a real app, this would queue a job to perform the HTTP request.
     */
    private function logDelivery(array $webhook, string $event, array $payload): void
    {
        $db = $this->dbal->database();

        $deliveryId = $db->insert('optilarity_webhook_deliveries')->values([
            'webhook_id' => $webhook['id'],
            'event'      => $event,
            'payload'    => json_encode($payload),
            'status'     => 'pending',
            'attempts'   => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ])->run();

        // Simulation: In a real system, a worker would pick this up and send the request.
        // For now, we just leave it in pending status.
    }
}
