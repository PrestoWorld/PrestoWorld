<?php

declare(strict_types=1);

namespace Modules\Customers\Admin;

use App\Foundation\Admin\TableList;
use App\Foundation\Admin\Column;
use App\Foundation\Admin\RowAction;
use App\Foundation\Admin\BulkAction;
use App\Foundation\Admin\ViewFilter;
use Cycle\Database\Injection\Fragment;

class CustomerTable extends TableList
{
    protected string $primaryKey   = 'id';
    protected int    $perPage      = 25;
    protected string $defaultOrderBy = 'created_at';
    protected string $defaultOrder   = 'DESC';
    protected string $singularName   = 'customer';
    protected string $pluralName     = 'customers';
    protected string $baseUrl        = '/dashboard/customers';

    public function __construct(private readonly \Cycle\Database\DatabaseInterface $db) {}

    protected function columns(): array
    {
        return [
            Column::make('cb', '')->width('40px'),
            Column::make('first_name', 'Name')->sortable()->primary(),
            Column::make('email', 'Email')->sortable(),
            Column::make('company', 'Company'),
            Column::make('country', 'Country'),
            Column::make('status', 'Status')->width('100px'),
            Column::make('created_at', 'Registered')->sortable()->width('140px'),
        ];
    }

    protected function bulkActions(): array
    {
        return [
            BulkAction::make('delete', 'Delete')->confirm('Delete selected customers? This cannot be undone.'),
            BulkAction::make('suspend', 'Suspend'),
        ];
    }

    protected function viewFilters(): array
    {
        $counts = $this->fetchStatusCounts();
        $views  = [
            ViewFilter::make('', 'All')->count(array_sum($counts)),
            ViewFilter::make('active',    'Active')->count($counts['active']    ?? 0),
            ViewFilter::make('suspended', 'Suspended')->count($counts['suspended'] ?? 0),
            ViewFilter::make('banned',    'Banned')->count($counts['banned']    ?? 0),
        ];

        foreach ($views as $i => $v) {
            $views[$i] = $this->currentView === $v->key ? $v->current() : $v;
        }
        return $views;
    }

    protected function rowActions(): array
    {
        return [
            RowAction::make('edit',   'Edit',   '/dashboard/customers/{id}/edit'),
            RowAction::make('delete', 'Delete', '/api/customers/{id}')
                ->method('DELETE')
                ->confirm('Delete this customer?')
                ->css('delete'),
        ];
    }

    protected function queryItems(int $page, int $perPage, string $orderBy, string $order, string $search, string $view): array
    {
        $offset = ($page - 1) * $perPage;
        $query  = $this->db->select('*')->from('optilarity_customers');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'LIKE', "%{$search}%")
                  ->orWhere('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('company', 'LIKE', "%{$search}%");
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
            'first_name' => $this->esc($row['first_name'] . ' ' . $row['last_name']),
            'email'      => "<a href=\"mailto:{$row['email']}\">{$this->esc($row['email'])}</a>",
            'status'     => "<span class=\"badge badge-{$row['status']}\">{$row['status']}</span>",
            'created_at' => $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : '—',
            default      => $this->esc($row[$column] ?? ''),
        };
    }

    private function fetchStatusCounts(): array
    {
        $rows = $this->db->select('status', new Fragment('COUNT(id) as cnt'))
            ->from('optilarity_customers')
            ->groupBy('status')
            ->fetchAll();

        $counts = [];
        foreach ($rows as $r) {
            $counts[$r['status']] = (int)$r['cnt'];
        }
        return $counts;
    }
}
