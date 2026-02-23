<?php

declare(strict_types=1);

namespace Modules\LicenseManager\Admin;

use App\Foundation\Admin\TableList;
use App\Foundation\Admin\Column;
use App\Foundation\Admin\RowAction;
use App\Foundation\Admin\BulkAction;
use App\Foundation\Admin\ViewFilter;
use Cycle\Database\Injection\Expression;

class LicenseTable extends TableList
{
    protected string $primaryKey     = 'id';
    protected int    $perPage        = 25;
    protected string $defaultOrderBy = 'created_at';
    protected string $defaultOrder   = 'DESC';
    protected string $singularName   = 'license';
    protected string $pluralName     = 'licenses';
    protected string $baseUrl        = '/dashboard/licenses';

    public function __construct(private readonly \Cycle\Database\DatabaseInterface $db) {}

    protected function columns(): array
    {
        return [
            Column::make('cb', '')->width('40px'),
            Column::make('license_key',      'License Key')->primary()->width('260px'),
            Column::make('customer_id',      'Customer')->sortable(),
            Column::make('activations_used', 'Activations')->width('110px'),
            Column::make('status',           'Status')->width('100px'),
            Column::make('expires_at',       'Expires')->sortable()->width('120px'),
            Column::make('created_at',       'Created')->sortable()->width('130px'),
        ];
    }

    protected function bulkActions(): array
    {
        return [
            BulkAction::make('revoke', 'Revoke')->confirm('Revoke selected licenses?'),
            BulkAction::make('delete', 'Delete')->confirm('Delete selected licenses?'),
        ];
    }

    protected function viewFilters(): array
    {
        $counts = $this->fetchStatusCounts();
        $views  = [
            ViewFilter::make('', 'All')->count(array_sum($counts)),
            ViewFilter::make('active',    'Active')->count($counts['active']    ?? 0),
            ViewFilter::make('expired',   'Expired')->count($counts['expired']   ?? 0),
            ViewFilter::make('suspended', 'Suspended')->count($counts['suspended'] ?? 0),
            ViewFilter::make('revoked',   'Revoked')->count($counts['revoked']   ?? 0),
        ];
        foreach ($views as $i => $v) { $views[$i] = $this->currentView === $v->key ? $v->current() : $v; }
        return $views;
    }

    protected function rowActions(): array
    {
        return [
            RowAction::make('edit',   'Edit',   '/dashboard/licenses/{id}/edit'),
            RowAction::make('revoke', 'Revoke', '/api/licenses/{id}/revoke')->method('POST')->confirm('Revoke this license?'),
            RowAction::make('delete', 'Delete', '/api/licenses/{id}')->method('DELETE')->confirm('Delete this license?')->css('delete'),
        ];
    }

    protected function queryItems(int $page, int $perPage, string $orderBy, string $order, string $search, string $view): array
    {
        $offset = ($page - 1) * $perPage;
        $query  = $this->db->select('*')->from('optilarity_licenses');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('license_key', 'LIKE', "%{$search}%");
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
            'license_key'      => "<code style='font-size:12px;background:#f8fafc;padding:2px 6px;border-radius:4px'>{$this->esc($row['license_key'])}</code>",
            'activations_used' => ($row['activations_used'] ?? 0) . ' / ' . ($row['max_activations'] ?? 1),
            'status'           => "<span class=\"badge badge-{$row['status']}\">{$row['status']}</span>",
            'expires_at'       => $row['expires_at'] ? date('M d, Y', strtotime($row['expires_at'])) : '<em style="color:#94a3b8">Lifetime</em>',
            'created_at'       => $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : '—',
            default            => $this->esc($row[$column] ?? ''),
        };
    }

    private function fetchStatusCounts(): array
    {
        $rows = $this->db->select('status')->columns(['status', 'cnt' => 'COUNT(id)'])->from('optilarity_licenses')->groupBy('status')->fetchAll();
        $c = [];
        foreach ($rows as $r) { $c[$r['status']] = (int)$r['cnt']; }
        return $c;
    }
}
