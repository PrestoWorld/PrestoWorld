<?php

declare(strict_types=1);

namespace Modules\Memberships\Admin;

use App\Foundation\Admin\TableList;
use App\Foundation\Admin\Column;
use App\Foundation\Admin\RowAction;
use App\Foundation\Admin\BulkAction;
use App\Foundation\Admin\ViewFilter;
use Cycle\Database\Injection\Expression;

class MembershipTable extends TableList
{
    protected string $primaryKey     = 'id';
    protected int    $perPage        = 25;
    protected string $defaultOrderBy = 'created_at';
    protected string $defaultOrder   = 'DESC';
    protected string $singularName   = 'membership';
    protected string $pluralName     = 'memberships';
    protected string $baseUrl        = '/dashboard/memberships';

    public function __construct(private readonly \Cycle\Database\DatabaseInterface $db) {}

    protected function columns(): array
    {
        return [
            Column::make('cb', '')->width('40px'),
            Column::make('customer_id', 'Customer')->sortable()->primary(),
            Column::make('plan_id',     'Plan')->sortable(),
            Column::make('status',      'Status')->width('110px'),
            Column::make('start_date',  'Start')->sortable()->width('120px'),
            Column::make('end_date',    'Renews/Ends')->sortable()->width('130px'),
            Column::make('auto_renew',  'Auto-Renew')->width('110px'),
        ];
    }

    protected function bulkActions(): array
    {
        return [
            BulkAction::make('cancel', 'Cancel')->confirm('Cancel selected memberships?'),
            BulkAction::make('delete', 'Delete')->confirm('Delete selected memberships?'),
        ];
    }

    protected function viewFilters(): array
    {
        $counts = $this->fetchStatusCounts();
        $views  = [
            ViewFilter::make('', 'All')->count(array_sum($counts)),
            ViewFilter::make('active',    'Active')->count($counts['active']    ?? 0),
            ViewFilter::make('trialing',  'Trialing')->count($counts['trialing']  ?? 0),
            ViewFilter::make('cancelled', 'Cancelled')->count($counts['cancelled'] ?? 0),
            ViewFilter::make('expired',   'Expired')->count($counts['expired']   ?? 0),
        ];
        foreach ($views as $i => $v) { $views[$i] = $this->currentView === $v->key ? $v->current() : $v; }
        return $views;
    }

    protected function rowActions(): array
    {
        return [
            RowAction::make('edit',   'Edit',   '/dashboard/memberships/{id}/edit'),
            RowAction::make('cancel', 'Cancel', '/api/memberships/{id}')->method('DELETE')->confirm('Cancel this membership?'),
        ];
    }

    protected function queryItems(int $page, int $perPage, string $orderBy, string $order, string $search, string $view): array
    {
        $offset = ($page - 1) * $perPage;
        $query  = $this->db->select('m.*', 'p.name as plan_name')
            ->from('optilarity_memberships as m')
            ->leftJoin('optilarity_membership_plans as p')->on('p.id', 'm.plan_id');

        if ($search) {
            $query->where('m.customer_id', 'LIKE', "%{$search}%");
        }
        if ($view) { $query->where('m.status', $view); }

        $total = (clone $query)->count('m.id');
        $items = $query->orderBy("m.{$orderBy}", $order)->limit($perPage)->offset($offset)->fetchAll();

        return ['items' => $items, 'total' => (int)$total];
    }

    protected function cellValue(string $column, array $row): string
    {
        return match ($column) {
            'customer_id' => "<a href=\"/dashboard/customers/{$row['customer_id']}/edit\">Customer #{$row['customer_id']}</a>",
            'plan_id'     => $this->esc($row['plan_name'] ?? 'Plan #' . $row['plan_id']),
            'status'      => "<span class=\"badge badge-{$row['status']}\">{$row['status']}</span>",
            'start_date'  => $row['start_date'] ? date('M d, Y', strtotime($row['start_date'])) : '—',
            'end_date'    => $row['end_date']   ? date('M d, Y', strtotime($row['end_date']))   : '<em style="color:#94a3b8">Lifetime</em>',
            'auto_renew'  => $row['auto_renew'] ? '<span class="badge badge-active">Yes</span>' : '<span style="color:#94a3b8">No</span>',
            default       => $this->esc($row[$column] ?? ''),
        };
    }

    private function fetchStatusCounts(): array
    {
        $rows = $this->db->select('status')->columns(['status', 'cnt' => 'COUNT(id)'])->from('optilarity_memberships')->groupBy('status')->fetchAll();
        $c = [];
        foreach ($rows as $r) { $c[$r['status']] = (int)$r['cnt']; }
        return $c;
    }
}
