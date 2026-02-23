<?php

declare(strict_types=1);

namespace Modules\Webhooks\Admin;

use App\Foundation\Admin\TableList;
use App\Foundation\Admin\Column;
use App\Foundation\Admin\RowAction;
use App\Foundation\Admin\BulkAction;

class WebhookTable extends TableList
{
    protected string $primaryKey   = 'id';
    protected int    $perPage      = 25;
    protected string $defaultOrderBy = 'created_at';
    protected string $defaultOrder   = 'DESC';
    protected string $singularName   = 'webhook';
    protected string $pluralName     = 'webhooks';
    protected string $baseUrl        = '/dashboard/webhooks';

    public function __construct(private readonly \Cycle\Database\DatabaseInterface $db) {}

    protected function columns(): array
    {
        return [
            Column::make('cb', '')->width('40px'),
            Column::make('name',      'Name')->sortable()->primary(),
            Column::make('url',       'Endpoint URL'),
            Column::make('events',    'Events'),
            Column::make('is_active', 'Active')->width('80px'),
            Column::make('created_at','Created')->sortable()->width('130px'),
        ];
    }

    protected function bulkActions(): array
    {
        return [
            BulkAction::make('delete',   'Delete')->confirm('Delete selected webhooks?'),
            BulkAction::make('disable',  'Disable'),
        ];
    }

    protected function rowActions(): array
    {
        return [
            RowAction::make('edit',      'Edit',       '/dashboard/webhooks/{id}/edit'),
            RowAction::make('deliveries','Deliveries', '/dashboard/webhooks/{id}/deliveries'),
            RowAction::make('delete',    'Delete',     '/api/webhooks/{id}')->method('DELETE')->confirm('Delete this webhook?')->css('delete'),
        ];
    }

    protected function queryItems(int $page, int $perPage, string $orderBy, string $order, string $search, string $view): array
    {
        $offset = ($page - 1) * $perPage;
        $query  = $this->db->select('*')->from('optilarity_webhooks');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('url', 'LIKE', "%{$search}%");
            });
        }

        $total = (clone $query)->count('id');
        $items = $query->orderBy($orderBy, $order)->limit($perPage)->offset($offset)->fetchAll();

        return ['items' => $items, 'total' => (int)$total];
    }

    protected function cellValue(string $column, array $row): string
    {
        return match ($column) {
            'url'       => "<span style='font-family:monospace;font-size:12px;word-break:break-all'>{$this->esc($row['url'])}</span>",
            'events'    => $this->renderEvents($row['events'] ?? '[]'),
            'is_active' => $row['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-expired">Inactive</span>',
            'created_at'=> $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : '—',
            default     => $this->esc($row[$column] ?? ''),
        };
    }

    private function renderEvents(string $json): string
    {
        $events = json_decode($json, true) ?: [];
        if (empty($events)) { return '<em style="color:#94a3b8">None</em>'; }
        $tags = array_map(fn($e) => "<span style='background:#eef2ff;color:#4338ca;padding:1px 6px;border-radius:4px;font-size:11px'>{$this->esc($e)}</span>", array_slice($events, 0, 3));
        $more = count($events) > 3 ? ' <span style="color:#94a3b8">+' . (count($events) - 3) . ' more</span>' : '';
        return implode(' ', $tags) . $more;
    }
}
