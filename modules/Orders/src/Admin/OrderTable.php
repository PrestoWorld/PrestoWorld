<?php

declare(strict_types=1);

namespace Modules\Orders\Admin;

use App\Foundation\Admin\TableList;
use App\Foundation\Admin\Column;
use App\Foundation\Admin\RowAction;
use App\Foundation\Admin\BulkAction;
use App\Foundation\Admin\ViewFilter;
use Cycle\Database\Injection\Fragment;

class OrderTable extends TableList
{
    protected string $primaryKey     = 'id';
    protected int    $perPage        = 25;
    protected string $defaultOrderBy = 'created_at';
    protected string $defaultOrder   = 'DESC';
    protected string $singularName   = 'order';
    protected string $pluralName     = 'orders';
    protected string $baseUrl        = '/dashboard/orders';

    public function __construct(private readonly \Cycle\Database\DatabaseInterface $db) {}

    protected function columns(): array
    {
        return [
            Column::make('cb', '')->width('40px'),
            Column::make('order_number', 'Order #')->sortable()->primary()->width('160px'),
            Column::make('customer_email', 'Customer')->sortable(),
            Column::make('total', 'Total')->sortable()->width('110px'),
            Column::make('status', 'Status')->width('120px'),
            Column::make('payment_status', 'Payment')->width('110px'),
            Column::make('created_at', 'Date')->sortable()->width('140px'),
        ];
    }

    protected function bulkActions(): array
    {
        return [
            BulkAction::make('delete', 'Delete')->confirm('Delete selected orders?'),
        ];
    }

    protected function viewFilters(): array
    {
        $counts = $this->fetchStatusCounts();
        $views  = [
            ViewFilter::make('', 'All')->count(array_sum($counts)),
            ViewFilter::make('pending',    'Pending')->count($counts['pending']    ?? 0),
            ViewFilter::make('processing', 'Processing')->count($counts['processing'] ?? 0),
            ViewFilter::make('completed',  'Completed')->count($counts['completed']  ?? 0),
            ViewFilter::make('cancelled',  'Cancelled')->count($counts['cancelled']  ?? 0),
        ];
        foreach ($views as $i => $v) {
            $views[$i] = $this->currentView === $v->key ? $v->current() : $v;
        }
        return $views;
    }

    protected function rowActions(): array
    {
        return [
            RowAction::make('view',   'View',   '/dashboard/orders/{id}'),
            RowAction::make('edit',   'Edit',   '/dashboard/orders/{id}/edit'),
            RowAction::make('delete', 'Delete', '/api/orders/{id}')->method('DELETE')->confirm('Delete this order?')->css('delete'),
        ];
    }

    protected function queryItems(int $page, int $perPage, string $orderBy, string $order, string $search, string $view): array
    {
        $offset = ($page - 1) * $perPage;
        $query  = $this->db->select('*')->from('optilarity_orders');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_email', 'LIKE', "%{$search}%")
                  ->orWhere('transaction_id', 'LIKE', "%{$search}%");
            });
        }
        if ($view) {
            $query->where('status', $view);
        }

        $total = (clone $query)->count('id');
        $items = $query->orderBy($orderBy, $order)->limit($perPage)->offset($offset)->fetchAll();

        return ['items' => $items, 'total' => (int)$total];
    }

    protected function cellValue(string $column, array $row): string
    {
        return match ($column) {
            'order_number'   => "<strong>#{$this->esc($row['order_number'])}</strong>",
            'customer_email' => $this->esc($row['customer_email'] ?? '—'),
            'total'          => '<strong>$' . number_format((float)($row['total'] ?? 0), 2) . ' ' . $this->esc($row['currency'] ?? 'USD') . '</strong>',
            'status'         => "<span class=\"badge badge-{$row['status']}\">{$row['status']}</span>",
            'payment_status' => "<span class=\"badge badge-{$row['payment_status']}\">{$row['payment_status']}</span>",
            'created_at'     => $row['created_at'] ? date('M d, Y H:i', strtotime($row['created_at'])) : '—',
            default          => $this->esc($row[$column] ?? ''),
        };
    }

    private function fetchStatusCounts(): array
    {
        $rows = $this->db->select('status', new Fragment('COUNT(id) as cnt'))->from('optilarity_orders')->groupBy('status')->fetchAll();
        $c = [];
        foreach ($rows as $r) { $c[$r['status']] = (int)$r['cnt']; }
        return $c;
    }
}
