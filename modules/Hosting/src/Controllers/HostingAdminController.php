<?php

declare(strict_types=1);

namespace Modules\Hosting\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class HostingAdminController extends AdminController
{
    public function index(Request $request): Response
    {
        $db = $this->db();
        $hostings = $db->select('*')->from('optilarity_hostings')->fetchAll();
        
        $rows = '';
        foreach ($hostings as $h) {
            $rows .= "<tr>
                <td><strong>{$h['domain']}</strong></td>
                <td>Member #{$h['customer_id']}</td>
                <td><span class='badge badge-active'>{$h['status']}</span></td>
                <td>{$h['expiry_date']}</td>
                <td>
                    <a href='/dashboard/hosting/{$h['id']}/edit' class='btn-ghost-sm'>Edit</a>
                </td>
            </tr>";
        }

        if (empty($hostings)) {
            $rows = "<tr><td colspan='5' style='text-align:center; padding: 40px; color: #64748b;'>Chưa có dịch vụ hosting nào được kích hoạt.</td></tr>";
        }

        $content = <<<HTML
        <div class="presto-card">
            <div class="presto-card-header">
                <h2 class="presto-card-title">Quản lý Dịch vụ Hosting</h2>
                <div class="card-actions">
                    <a href="/dashboard/hosting/plans" class="presto-btn presto-btn-secondary">📦 Quản lý Gói cước</a>
                </div>
            </div>
            <div class="presto-card-body p-0">
                <table class="presto-list-table">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Khách hàng</th>
                            <th>Trạng thái</th>
                            <th>Hết hạn</th>
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

        return $this->htmlResponse($this->adminPage('Hosting Services', $content, [
            'new_label' => '+ Kích hoạt Hosting',
            'new_url' => '/dashboard/hosting/create'
        ]));
    }

    public function create(Request $request): Response
    {
        $db = $this->db();
        $plans = $db->select('id', 'name')->from('optilarity_hosting_plans')->run()->fetchAll();
        $customers = $db->select('id', 'first_name', 'last_name', 'email')->from('optilarity_customers')->run()->fetchAll();
        
        $form = $this->renderForm([], '/dashboard/hosting/create', 'POST', $plans, $customers);
        return $this->htmlResponse($this->adminPage('Kích hoạt Hosting', $form));
    }

    /** POST /dashboard/hosting/create */
    public function store(Request $request): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->insert('optilarity_hostings')->values([
                'customer_id' => (int)$body['customer_id'],
                'plan_id'     => (int)$body['plan_id'],
                'domain'      => $body['domain'] ?? '',
                'username'    => $body['username'] ?? null,
                'password'    => $body['password'] ?? null,
                'server_ip'   => $body['server_ip'] ?? null,
                'status'      => $body['status']    ?? 'pending',
                'expiry_date' => $body['expiry_date'] ? date('Y-m-d', strtotime($body['expiry_date'])) : null,
                'created_at'  => date('Y-m-d H:i:s'),
            ])->run();
            return $this->redirect('/dashboard/hosting');
        } catch (\Throwable $e) {
            $db = $this->db();
            $plans = $db->select('id', 'name')->from('optilarity_hosting_plans')->run()->fetchAll();
            $customers = $db->select('id', 'first_name', 'last_name', 'email')->from('optilarity_customers')->run()->fetchAll();
            $form = $this->notice($e->getMessage(), 'error') . $this->renderForm($body, '/dashboard/hosting/create', 'POST', $plans, $customers);
            return $this->htmlResponse($this->adminPage('Kích hoạt Hosting', $form));
        }
    }

    /** GET /dashboard/hosting/{id}/edit */
    public function edit(Request $request, int $id): Response
    {
        $db = $this->db();
        $row = $db->select('*')->from('optilarity_hostings')->where('id', $id)->run()->fetch();
        if (!$row) return $this->htmlResponse($this->adminPage('Not Found', 'Hosting not found'), 404);

        $plans = $db->select('id', 'name')->from('optilarity_hosting_plans')->run()->fetchAll();
        $customers = $db->select('id', 'first_name', 'last_name', 'email')->from('optilarity_customers')->run()->fetchAll();
        
        $form = $this->renderForm((array)$row, "/dashboard/hosting/{$id}/edit", 'PUT', $plans, $customers);
        return $this->htmlResponse($this->adminPage('Chỉnh sửa Hosting', $form));
    }

    /** PUT /dashboard/hosting/{id}/edit */
    public function update(Request $request, int $id): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->update('optilarity_hostings', [
                'plan_id'     => (int)$body['plan_id'],
                'domain'      => $body['domain'] ?? '',
                'status'      => $body['status']    ?? 'pending',
                'expiry_date' => $body['expiry_date'] ? date('Y-m-d', strtotime($body['expiry_date'])) : null,
                'updated_at'  => date('Y-m-d H:i:s'),
            ], ['id' => $id]);
            return $this->redirect('/dashboard/hosting');
        } catch (\Throwable $e) {
            $db = $this->db();
            $plans = $db->select('id', 'name')->from('optilarity_hosting_plans')->run()->fetchAll();
            $customers = $db->select('id', 'first_name', 'last_name', 'email')->from('optilarity_customers')->run()->fetchAll();
            $form = $this->notice($e->getMessage(), 'error') . $this->renderForm($body, "/dashboard/hosting/{$id}/edit", 'PUT', $plans, $customers);
            return $this->htmlResponse($this->adminPage('Chỉnh sửa Hosting', $form));
        }
    }

    private function renderForm(array $data, string $action, string $method, array $plans, array $customers): string
    {
        $statusOpts = ['pending' => 'Chờ xử lý', 'active' => 'Đang chạy', 'suspended' => 'Đã tạm dừng', 'terminated' => 'Đã hủy'];
        
        $planItems = [];
        foreach ($plans as $p) $planItems[] = ['value' => (string)$p['id'], 'label' => $p['name']];
        
        $custItems = [];
        foreach ($customers as $c) $custItems[] = ['value' => (string)$c['id'], 'label' => "{$c['first_name']} {$c['last_name']} ({$c['email']})"];

        $formContent = <<<HTML
        <div class="presto-form-section-head">
            <div class="icon-wrap">🌐</div>
            <h3>Thông tin Hosting & Tên miền</h3>
        </div>
        <div class="presto-grid">
            <div class="col-8">{$this->fieldGroup('Tên miền chính (Primary Domain)', $this->input('domain', 'text', $data['domain'] ?? '', 'example.com', true))}</div>
            <div class="col-4">{$this->fieldGroup('Trạng thái dịch vụ', $this->select('status', $statusOpts, $data['status'] ?? 'pending'))}</div>
            
            <div class="col-6">
                <label class="presto-field-label">Gói Hosting (Plan)</label>
                {$this->searchableSelect('plan_id', $planItems, $data['plan_id'] ?? '', 'Chọn gói hosting...')}
            </div>
            <div class="col-6">
                <label class="presto-field-label">Gán cho Khách hàng</label>
                {$this->searchableSelect('customer_id', $custItems, $data['customer_id'] ?? '', 'Tìm khách hàng...')}
            </div>
        </div>

        <div class="presto-form-section-head">
            <div class="icon-wrap">⚙️</div>
            <h3>Thông tin Kỹ thuật & Quản trị</h3>
        </div>
        <div class="presto-grid">
            <div class="col-4">{$this->fieldGroup('IP Máy chủ', $this->input('server_ip', 'text', $data['server_ip'] ?? '', '1.2.3.4'))}</div>
            <div class="col-4">{$this->fieldGroup('Tài khoản (Username)', $this->input('username', 'text', $data['username'] ?? ''))}</div>
            <div class="col-4">{$this->fieldGroup('Ngày hết hạn', $this->input('expiry_date', 'date', $data['expiry_date'] ?? ''))}</div>
        </div>

        <div style="margin-top: 48px; display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid var(--border); padding-top: 32px;">
            <a href="/dashboard/hosting" class="presto-btn presto-btn-secondary">Quay lại</a>
            <button type="submit" class="presto-btn presto-btn-primary">Lưu Cấu Hình Hosting</button>
        </div>
HTML;

        return $this->formCard('Chi tiết dịch vụ Hosting', $this->formOpen($action, $method) . $formContent . $this->formClose());
    }

    public function plans(Request $request): Response
    {
        $content = "<h2>Hosting Plans Management</h2><p>Coming soon...</p>";
        return $this->htmlResponse($this->adminPage('Hosting Plans', $content));
    }
}
