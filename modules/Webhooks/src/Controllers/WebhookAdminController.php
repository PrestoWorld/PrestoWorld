<?php

declare(strict_types=1);

namespace Modules\Webhooks\Controllers;

use App\Foundation\Admin\AdminController;
use Modules\Webhooks\Admin\WebhookTable;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class WebhookAdminController extends AdminController
{
    private const AVAILABLE_EVENTS = [
        'order.created', 'order.completed', 'order.cancelled', 'order.refunded',
        'invoice.created', 'invoice.paid', 'invoice.overdue',
        'license.activated', 'license.deactivated', 'license.expired', 'license.revoked',
        'membership.created', 'membership.cancelled', 'membership.expired',
        'customer.created', 'customer.updated',
    ];

    public function index(Request $request): Response
    {
        $table = (new WebhookTable($this->db()))->prepare($request);
        return $this->htmlResponse(
            $this->adminPage('Webhooks', $table->render(), [
                'new_url' => '/dashboard/webhooks/create',
                'new_label' => '+ Add Webhook',
            ])
        );
    }

    public function create(Request $request): Response
    {
        $form = $this->renderForm([], '/dashboard/webhooks/create');
        return $this->htmlResponse(
            $this->adminPage('Add Webhook', $form, ['breadcrumbs' => ['Webhooks' => '/dashboard/webhooks', 'Add' => '']])
        );
    }

    public function store(Request $request): Response
    {
        $body = (array)$request->post();
        try {
            $events = array_values(array_intersect($body['events'] ?? [], self::AVAILABLE_EVENTS));
            $this->db()->insert('optilarity_webhooks')->values([
                'name'       => $body['name']   ?? '',
                'url'        => $body['url']    ?? '',
                'secret'     => $body['secret'] ?: null,
                'events'     => json_encode($events),
                'is_active'  => isset($body['is_active']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ])->run();
            return $this->redirect('/dashboard/webhooks');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Add Webhook', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, '/dashboard/webhooks/create')));
        }
    }

    public function edit(Request $request, int $id): Response
    {
        $row = $this->db()->select('*')->from('optilarity_webhooks')->where('id', $id)->run()->fetch();
        if (!$row) { return $this->htmlResponse($this->adminPage('Not Found', $this->notice('Webhook not found.', 'error')), 404); }
        $row['events_array'] = json_decode($row['events'] ?? '[]', true);
        return $this->htmlResponse(
            $this->adminPage('Edit Webhook', $this->renderForm((array)$row, "/dashboard/webhooks/{$id}/edit", 'PUT'), ['breadcrumbs' => ['Webhooks' => '/dashboard/webhooks', 'Edit' => '']])
        );
    }

    public function update(Request $request, int $id): Response
    {
        $body   = (array)$request->post();
        $events = array_values(array_intersect($body['events'] ?? [], self::AVAILABLE_EVENTS));
        try {
            $this->db()->update('optilarity_webhooks', [
                'name'       => $body['name']   ?? '',
                'url'        => $body['url']    ?? '',
                'secret'     => $body['secret'] ?: null,
                'events'     => json_encode($events),
                'is_active'  => isset($body['is_active']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id]);
            return $this->redirect('/dashboard/webhooks');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Edit Webhook', $this->notice($e->getMessage(), 'error')));
        }
    }

    public function deliveries(Request $request, int $id): Response
    {
        $webhook    = $this->db()->select('*')->from('optilarity_webhooks')->where('id', $id)->run()->fetch();
        $deliveries = $this->db()->select('*')->from('optilarity_webhook_deliveries')
            ->where('webhook_id', $id)->orderBy('created_at', 'DESC')->limit(50)->fetchAll();

        $rows = '';
        foreach ($deliveries as $d) {
            $statusBadge = "<span class='badge badge-{$d['status']}'>{$d['status']}</span>";
            $code = $d['response_code'] ? "<code>{$d['response_code']}</code>" : '—';
            $rows .= "<tr><td>{$d['event']}</td><td>{$statusBadge}</td><td>{$code}</td><td>{$d['attempts']}</td><td>" . date('M d H:i', strtotime($d['created_at'])) . "</td></tr>";
        }

        $table = empty($deliveries)
            ? $this->notice('No deliveries logged yet.', 'info')
            : "<div class='presto-table-wrap'><table class='presto-list-table'><thead><tr><th>Event</th><th>Status</th><th>Code</th><th>Attempts</th><th>Date</th></tr></thead><tbody>{$rows}</tbody></table></div>";

        return $this->htmlResponse(
            $this->adminPage('Deliveries — ' . ($webhook['name'] ?? ''), $table, ['breadcrumbs' => ['Webhooks' => '/dashboard/webhooks', 'Deliveries' => '']])
        );
    }

    private function renderForm(array $data = [], string $action = '', string $method = 'POST'): string
    {
        $selectedEvents = $data['events_array'] ?? [];

        $eventCheckboxes = '';
        foreach (self::AVAILABLE_EVENTS as $event) {
            $checked = in_array($event, $selectedEvents, true) ? ' checked' : '';
            $eventCheckboxes .= "<label style='display:flex;align-items:center;gap:8px;margin-bottom:8px'>"
                              . "<input type='checkbox' name='events[]' value='{$event}'{$checked}> {$event}</label>";
        }

        $fields = $this->fieldGroup('Webhook Name *', $this->input('name', 'text', $data['name'] ?? '', 'My Webhook', true))
                . $this->fieldGroup('Endpoint URL *', $this->input('url', 'url', $data['url'] ?? '', 'https://example.com/hook', true))
                . $this->fieldGroup('Secret Key',     $this->input('secret', 'text', $data['secret'] ?? ''), 'Used to sign payloads (HMAC-SHA256). Leave blank to disable signing.')
                . $this->fieldGroup('Events',         "<div style='columns:2;gap:20px'>{$eventCheckboxes}</div>")
                . $this->fieldGroup('Active',         $this->checkbox('is_active', (bool)($data['is_active'] ?? true), 'Send events to this endpoint'))
                . $this->submitBar('Save Webhook', '/dashboard/webhooks');

        return $this->formCard('Webhook Configuration', $this->formOpen($action, $method) . $fields . $this->formClose());
    }
}
