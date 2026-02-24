<?php

declare(strict_types=1);

namespace Modules\WebServices\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class WebServiceAdminController extends AdminController
{
    public function index(Request $request): Response
    {
        $services = $this->db()->select('*')->from('optilarity_web_services')->run()->fetchAll();
        
        $rows = '';
        foreach ($services as $s) {
            $statusBadge = $s['status'] === 'active' ? 'badge-active' : 'badge-warning';
            $rows .= "<tr>
                <td><strong>{$s['name']}</strong><br><small class='text-dim'>{$s['slug']}</small></td>
                <td><span class='badge'>{$s['category']}</span></td>
                <td>" . number_format((float)$s['base_price'], 2) . "</td>
                <td><span class='badge {$statusBadge}'>{$s['status']}</span></td>
                <td>
                    <a href='/dashboard/web-services/{$s['id']}/edit' class='btn-ghost-sm'>Sửa</a>
                </td>
            </tr>";
        }

        if (empty($services)) {
            $rows = "<tr><td colspan='5' style='text-align:center; padding: 40px; color: #64748b;'>Chưa có dịch vụ nào.</td></tr>";
        }

        $content = <<<HTML
        <div class="presto-card">
            <div class="presto-card-header">
                <h2 class="presto-card-title">Quản lý Dịch vụ Kỹ thuật</h2>
            </div>
            <div class="presto-card-body p-0">
                <table class="presto-list-table">
                    <thead>
                        <tr>
                            <th>Tên dịch vụ</th>
                            <th>Danh mục</th>
                            <th>Giá cơ bản</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        </div>
HTML;

        return $this->htmlResponse($this->adminPage('Web Services', $content, [
            'new_label' => '+ Thêm dịch vụ',
            'new_url' => '/dashboard/web-services/create'
        ]));
    }

    public function create(Request $request): Response
    {
        $form = $this->renderForm([], '/dashboard/web-services/create');
        return $this->htmlResponse($this->adminPage('Thêm dịch vụ mới', $form));
    }

    public function store(Request $request): Response
    {
        $body = (array)$request->post();
        try {
            if (empty($body['slug'])) {
                $body['slug'] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $body['name'] ?? 'service'));
            }

            $this->db()->insert('optilarity_web_services')->values([
                'name'          => $body['name'] ?? '',
                'slug'          => $body['slug'],
                'category'      => $body['category'] ?? 'uncategorized',
                'description'   => $body['description'] ?? '',
                'base_price'    => (float)($body['base_price'] ?? 0),
                'warranty_days' => (int)($body['warranty_days'] ?? 30),
                'status'        => $body['status'] ?? 'active',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ])->run();

            return $this->redirect('/dashboard/web-services');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Thêm dịch vụ', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, '/dashboard/web-services/create')));
        }
    }

    public function edit(Request $request, int $id): Response
    {
        $row = $this->db()->select('*')->from('optilarity_web_services')->where('id', $id)->run()->fetch();
        if (!$row) return $this->htmlResponse($this->adminPage('Not Found', 'Service not found'), 404);

        return $this->htmlResponse($this->adminPage('Sửa dịch vụ', $this->renderForm((array)$row, "/dashboard/web-services/{$id}/edit", 'PUT')));
    }

    public function update(Request $request, int $id): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->update('optilarity_web_services', [
                'name'          => $body['name'] ?? '',
                'slug'          => $body['slug'] ?? '',
                'category'      => $body['category'] ?? '',
                'description'   => $body['description'] ?? '',
                'base_price'    => (float)($body['base_price'] ?? 0),
                'warranty_days' => (int)($body['warranty_days'] ?? 0),
                'status'        => $body['status'] ?? 'active',
                'updated_at'    => date('Y-m-d H:i:s'),
            ], ['id' => $id]);

            return $this->redirect('/dashboard/web-services');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Sửa dịch vụ', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, "/dashboard/web-services/{$id}/edit", 'PUT')));
        }
    }

    private function renderForm(array $data, string $action, string $method = 'POST'): string
    {
        $statusOpts = ['active' => 'Đang hoạt động', 'inactive' => 'Tạm ngưng'];
        $catOpts = [
            'maintenance' => 'Bảo trì',
            'optimization' => 'Tối ưu hóa',
            'security' => 'Bảo mật',
            'conversion' => 'Chuyển đổi',
            'migration' => 'Di chuyển',
            'other' => 'Khác'
        ];

        $formContent = <<<HTML
        <div class="presto-form-section-head">
            <div class="icon-wrap">⚙️</div>
            <h3>Thông tin dịch vụ</h3>
        </div>
        <div class="presto-grid">
            <div class="col-8">{$this->fieldGroup('Tên dịch vụ', $this->input('name', 'text', $data['name'] ?? '', 'Tên hiển thị', true))}</div>
            <div class="col-4">{$this->fieldGroup('Đường dẫn (Slug)', $this->input('slug', 'text', $data['slug'] ?? '', 'slug-url'))}</div>
            
            <div class="col-6">{$this->fieldGroup('Danh mục', $this->select('category', $catOpts, $data['category'] ?? 'maintenance'))}</div>
            <div class="col-6">{$this->fieldGroup('Trạng thái', $this->select('status', $statusOpts, $data['status'] ?? 'active'))}</div>
            
            <div class="col-12">{$this->fieldGroup('Mô tả dịch vụ', $this->textarea('description', $data['description'] ?? '', 'Chi tiết công việc...'))}</div>
            
            <div class="col-6">{$this->fieldGroup('Giá cơ bản ($)', $this->input('base_price', 'number', $data['base_price'] ?? 0))}</div>
            <div class="col-6">{$this->fieldGroup('Ngày bảo hành', $this->input('warranty_days', 'number', $data['warranty_days'] ?? 30))}</div>
        </div>

        <div style="margin-top: 48px; display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid var(--border); padding-top: 32px;">
            <a href="/dashboard/web-services" class="presto-btn presto-btn-secondary">Hủy bỏ</a>
            <button type="submit" class="presto-btn presto-btn-primary">Lưu dịch vụ</button>
        </div>
HTML;

        return $this->formCard('Cấu hình Dịch vụ', $this->formOpen($action, $method) . $formContent . $this->formClose());
    }
}
