<?php

declare(strict_types=1);

namespace Modules\SoftwareCatalog\Admin;

use App\Foundation\Admin\TableList;
use App\Foundation\Admin\Column;
use App\Foundation\Admin\RowAction;
use App\Foundation\Admin\BulkAction;
use App\Foundation\Admin\ViewFilter;
use Cycle\Database\Injection\Fragment;

class SoftwareCatalogTable extends TableList
{
    protected string $primaryKey     = 'id';
    protected int    $perPage        = 25;
    protected string $defaultOrderBy = 'created_at';
    protected string $defaultOrder   = 'DESC';
    protected string $singularName   = 'product';
    protected string $pluralName     = 'products';
    protected string $baseUrl        = '/dashboard/catalog';

    public function __construct(private readonly \Cycle\Database\DatabaseInterface $db) {}

    protected function columns(): array
    {
        return [
            Column::make('cb', '')->width('40px'),
            Column::make('name',       'Product Name')->sortable()->primary(),
            Column::make('type',       'Type')->width('100px'),
            Column::make('version',    'Version')->width('100px'),
            Column::make('price',      'Price')->sortable()->width('110px'),
            Column::make('status',     'Status')->width('100px'),
            Column::make('created_at', 'Added')->sortable()->width('120px'),
        ];
    }

    protected function bulkActions(): array
    {
        return [
            BulkAction::make('delete',     'Delete')->confirm('Delete selected products?'),
            BulkAction::make('deprecate',  'Mark Deprecated'),
        ];
    }

    protected function viewFilters(): array
    {
        $counts = $this->fetchTypeCounts();
        $views  = [
            ViewFilter::make('', 'All')->count(array_sum($counts))->queryVar('type'),
            ViewFilter::make('software', 'Software')->count($counts['software'] ?? 0)->queryVar('type'),
            ViewFilter::make('plugin',   'Plugins')->count($counts['plugin']   ?? 0)->queryVar('type'),
            ViewFilter::make('theme',    'Themes')->count($counts['theme']    ?? 0)->queryVar('type'),
        ];
        foreach ($views as $i => $v) { $views[$i] = ($this->currentView === $v->key) ? $v->current() : $v; }
        return $views;
    }

    protected function rowActions(): array
    {
        return [
            RowAction::make('edit',   'Edit',   '/dashboard/catalog/{id}/edit'),
            RowAction::make('delete', 'Delete', '/api/catalog/{id}')->method('DELETE')->confirm('Delete this product?')->css('delete'),
        ];
    }

    protected function queryItems(int $page, int $perPage, string $orderBy, string $order, string $search, string $view): array
    {
        $offset = ($page - 1) * $perPage;
        $params = $this->getRequestParams();
        $type   = $params['type'] ?? $view;

        $query = $this->db->select('*')->from('optilarity_software_products');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('author', 'LIKE', "%{$search}%");
            });
        }
        if ($type) { $query->where('type', $type); }

        $total = (clone $query)->count('id');
        $items = $query->orderBy($orderBy, $order)->limit($perPage)->offset($offset)->fetchAll();

        return ['items' => $items, 'total' => (int)$total];
    }

    protected function cellValue(string $column, array $row): string
    {
        return match ($column) {
            'name'       => $this->esc($row['name']),
            'type'       => "<span class=\"badge badge-{$row['type']}\">{$row['type']}</span>",
            'price'      => $row['price'] > 0 ? '$' . number_format((float)$row['price'], 2) : '<em style="color:#94a3b8">Free</em>',
            'status'     => "<span class=\"badge badge-{$row['status']}\">{$row['status']}</span>",
            'version'    => $row['version'] ? 'v' . $this->esc($row['version']) : '—',
            'created_at' => $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : '—',
            default      => $this->esc($row[$column] ?? ''),
        };
    }

    private function getRequestParams(): array { return $_GET ?? []; }

    private function fetchTypeCounts(): array
    {
        $rows = $this->db->select('type', new Fragment('COUNT(id) as cnt'))->from('optilarity_software_products')->groupBy('type')->fetchAll();
        $c = [];
        foreach ($rows as $r) { $c[$r['type']] = (int)$r['cnt']; }
        return $c;
    }
}
