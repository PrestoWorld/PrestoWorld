<?php

declare(strict_types=1);

namespace Modules\Invoices\Admin;

use App\Foundation\Admin\TableList;
use App\Foundation\Admin\Column;
use App\Foundation\Admin\RowAction;
use App\Foundation\Admin\BulkAction;
use App\Foundation\Admin\ViewFilter;
use Cycle\Database\Injection\Fragment;

class InvoiceTable extends TableList
{
    protected string $primaryKey     = 'id';
    protected int    $perPage        = 25;
    protected string $defaultOrderBy = 'created_at';
    protected string $defaultOrder   = 'DESC';
    protected string $singularName   = 'invoice';
    protected string $pluralName     = 'invoices';
    protected string $baseUrl        = '/dashboard/invoices';

    public function __construct(private readonly \Cycle\Database\DatabaseInterface $db) {}

    protected function columns(): array
    {
        return [
            Column::make('cb', '')->width('40px'),
            Column::make('invoice_number', 'Invoice #')->sortable()->primary()->width('150px'),
            Column::make('customer_name',  'Customer')->sortable(),
            Column::make('total',          'Amount')->sortable()->width('120px'),
            Column::make('status',         'Status')->width('110px'),
            Column::make('due_date',       'Due')->sortable()->width('120px'),
            Column::make('created_at',     'Created')->sortable()->width('130px'),
        ];
    }

    protected function bulkActions(): array
    {
        return [
            BulkAction::make('delete', 'Delete')->confirm('Delete selected invoices?'),
            BulkAction::make('mark_sent', 'Mark as Sent'),
        ];
    }

    protected function viewFilters(): array
    {
        $counts = $this->fetchStatusCounts();
        $views  = [
            ViewFilter::make('', 'All')->count(array_sum($counts)),
            ViewFilter::make('draft',     'Draft')->count($counts['draft']     ?? 0),
            ViewFilter::make('sent',      'Sent')->count($counts['sent']      ?? 0),
            ViewFilter::make('paid',      'Paid')->count($counts['paid']      ?? 0),
            ViewFilter::make('overdue',   'Overdue')->count($counts['overdue']   ?? 0),
            ViewFilter::make('cancelled', 'Cancelled')->count($counts['cancelled'] ?? 0),
        ];
        foreach ($views as $i => $v) { $views[$i] = $this->currentView === $v->key ? $v->current() : $v; }
        return $views;
    }

    protected function rowActions(): array
    {
        return [
            RowAction::make('edit',   'Edit',   '/dashboard/invoices/{id}/edit'),
            RowAction::make('delete', 'Delete', '/api/invoices/{id}')->method('DELETE')->confirm('Delete this invoice?')->css('delete'),
        ];
    }

    protected function queryItems(int $page, int $perPage, string $orderBy, string $order, string $search, string $view): array
    {
        $offset = ($page - 1) * $perPage;
        $query  = $this->db->select('*')->from('optilarity_invoices');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_email', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%");
            });
        }
        if ($view) { $query->where('status', $view); }

        $total = (clone $query)->count('id');
        $items = $query->orderBy($orderBy, $order)->limit($perPage)->offset($offset)->fetchAll();

        return ['items' => $items, 'total' => (int)$total];
    }

    protected function cellValue(string $column, array $row): string
    {
        return match ($column) {
            'invoice_number' => "<strong>{$this->esc($row['invoice_number'])}</strong>",
            'customer_name'  => $this->esc($row['customer_name'] ?? $row['customer_email'] ?? '—'),
            'total'          => '$' . number_format((float)($row['total'] ?? 0), 2) . ' ' . $this->esc($row['currency'] ?? 'USD'),
            'status'         => "<span class=\"badge badge-{$row['status']}\">{$row['status']}</span>",
            'due_date'       => $row['due_date'] ? date('M d, Y', strtotime($row['due_date'])) : '—',
            'created_at'     => $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : '—',
            default          => $this->esc($row[$column] ?? ''),
        };
    }

    private function fetchStatusCounts(): array
    {
        $rows = $this->db->select('status', new Fragment('COUNT(id) as cnt'))->from('optilarity_invoices')->groupBy('status')->fetchAll();
        $c = [];
        foreach ($rows as $r) { $c[$r['status']] = (int)$r['cnt']; }
        return $c;
    }
}
