<?php

declare(strict_types=1);

namespace Modules\Domains\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class DomainsAdminController extends AdminController
{
    public function index(Request $request): Response
    {
        $db = $this->db();
        $domains = $db->select('*')->from('optilarity_domains')->fetchAll();
        
        $rows = '';
        foreach ($domains as $d) {
            $rows .= "<tr>
                <td><strong>{$d['domain_name']}</strong></td>
                <td>{$d['registrar']}</td>
                <td><span class='badge badge-active'>{$d['status']}</span></td>
                <td>{$d['expiry_date']}</td>
                <td>
                    <a href='/dashboard/domains/{$d['id']}/edit' class='btn-ghost-sm'>Edit</a>
                    <button class='btn-ghost-sm'>DNS</button>
                </td>
            </tr>";
        }

        if (empty($domains)) {
            $rows = "<tr><td colspan='5' style='text-align:center; padding: 40px; color: #64748b;'>Chưa có tên miền nào được đăng ký.</td></tr>";
        }

        $content = <<<HTML
        <div class="presto-card">
            <div class="presto-card-header">
                <h2 class="presto-card-title">Quản lý Tên miền</h2>
            </div>
            <div class="presto-card-body p-0">
                <table class="presto-list-table">
                    <thead>
                        <tr>
                            <th>Tên miền</th>
                            <th>Nhà đăng ký</th>
                            <th>Trạng thái</th>
                            <th>Ngày hết hạn</th>
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

        return $this->htmlResponse($this->adminPage('Domain Management', $content, [
            'new_label' => '+ Đăng ký Tên miền',
            'new_url' => '/dashboard/domains/create'
        ]));
    }

    /** GET /dashboard/domains/create */
    public function create(Request $request): Response
    {
        $customers = $this->db()->select('id', 'first_name', 'last_name', 'email')->from('optilarity_customers')->run()->fetchAll();
        $form = $this->renderForm([], '/dashboard/domains/create', 'POST', $customers);
        return $this->htmlResponse($this->adminPage('Đăng ký Tên miền mới', $form));
    }

    /** POST /dashboard/domains/create */
    public function store(Request $request): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->insert('optilarity_domains')->values([
                'customer_id'   => (int)$body['customer_id'],
                'domain_name'   => $body['domain_name'] ?? '',
                'registrar'     => $body['registrar']   ?? 'Manual',
                'status'        => $body['status']      ?? 'active',
                'expiry_date'   => $body['expiry_date'] ? date('Y-m-d', strtotime($body['expiry_date'])) : null,
                'epp_code'      => $body['epp_code']     ?? null,
                'nameservers'   => $body['nameservers']  ?? null,
                'created_at'    => date('Y-m-d H:i:s'),
            ])->run();
            return $this->redirect('/dashboard/domains');
        } catch (\Throwable $e) {
            $customers = $this->db()->select('id', 'first_name', 'last_name', 'email')->from('optilarity_customers')->run()->fetchAll();
            $form = $this->notice($e->getMessage(), 'error') . $this->renderForm($body, '/dashboard/domains/create', 'POST', $customers);
            return $this->htmlResponse($this->adminPage('Đăng ký Tên miền mới', $form));
        }
    }

    /** GET /dashboard/domains/{id}/edit */
    public function edit(Request $request, int $id): Response
    {
        $db = $this->db();
        $row = $db->select('*')->from('optilarity_domains')->where('id', $id)->run()->fetch();
        if (!$row) return $this->htmlResponse($this->adminPage('Not Found', 'Domain not found'), 404);

        $customers = $db->select('id', 'first_name', 'last_name', 'email')->from('optilarity_customers')->run()->fetchAll();
        $form = $this->renderForm((array)$row, "/dashboard/domains/{$id}/edit", 'PUT', $customers);
        return $this->htmlResponse($this->adminPage('Chỉnh sửa Tên miền', $form));
    }

    /** PUT /dashboard/domains/{id}/edit */
    public function update(Request $request, int $id): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->update('optilarity_domains', [
                'status'        => $body['status']      ?? 'active',
                'expiry_date'   => $body['expiry_date'] ? date('Y-m-d', strtotime($body['expiry_date'])) : null,
                'registrar'     => $body['registrar']   ?? 'Manual',
                'updated_at'    => date('Y-m-d H:i:s'),
            ], ['id' => $id]);
            return $this->redirect('/dashboard/domains');
        } catch (\Throwable $e) {
            $customers = $this->db()->select('id', 'first_name', 'last_name', 'email')->from('optilarity_customers')->run()->fetchAll();
            $form = $this->notice($e->getMessage(), 'error') . $this->renderForm($body, "/dashboard/domains/{$id}/edit", 'PUT', $customers);
            return $this->htmlResponse($this->adminPage('Chỉnh sửa Tên miền', $form));
        }
    }

    private function renderForm(array $data, string $action, string $method, array $customers): string
    {
        $statusOpts = ['active' => 'Đang hoạt động', 'expired' => 'Đã hết hạn', 'pending' => 'Đang xử lý', 'transferred' => 'Đã chuyển đi'];
        
        $custItems = [];
        foreach ($customers as $c) $custItems[] = ['value' => (string)$c['id'], 'label' => "{$c['first_name']} {$c['last_name']} ({$c['email']})"];

        $formContent = <<<HTML
        <div class="presto-form-section-head">
            <div class="icon-wrap">🌍</div>
            <h3>Thông tin Tên miền & Chủ sở hữu</h3>
        </div>
        <div class="presto-grid">
            <div class="col-8">{$this->fieldGroup('Tên miền (Domain Name)', $this->input('domain_name', 'text', $data['domain_name'] ?? '', 'example.com', true))}</div>
            <div class="col-4">{$this->fieldGroup('Trạng thái', $this->select('status', $statusOpts, $data['status'] ?? 'active'))}</div>
            
            <div class="col-12">
                <label class="presto-field-label">Chủ sở hữu (Khách hàng)</label>
                {$this->searchableSelect('customer_id', $custItems, $data['customer_id'] ?? '', 'Tìm chủ sở hữu...')}
            </div>
        </div>

        <div class="presto-form-section-head">
            <div class="icon-wrap">🛡️</div>
            <h3>Thông tin Đăng ký & Kỹ thuật</h3>
        </div>
        <div class="presto-grid">
            <div class="col-6">{$this->fieldGroup('Nhà đăng ký (Registrar)', $this->input('registrar', 'text', $data['registrar'] ?? '', 'Manual'))}</div>
            <div class="col-6">{$this->fieldGroup('Ngày hết hạn', $this->input('expiry_date', 'date', $data['expiry_date'] ?? ''))}</div>
            <div class="col-6">{$this->fieldGroup('Mã EPP / Auth Code', $this->input('epp_code', 'text', $data['epp_code'] ?? ''))}</div>
            <div class="col-6">{$this->fieldGroup('Nameservers (JSON or lines)', $this->textarea('nameservers', $data['nameservers'] ?? '', 'ns1.example.com...', 2))}</div>
        </div>

        <div style="margin-top: 48px; display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid var(--border); padding-top: 32px;">
            <a href="/dashboard/domains" class="presto-btn presto-btn-secondary">Quay lại</a>
            <button type="submit" class="presto-btn presto-btn-primary">Lưu Thông Tin Tên Miền</button>
        </div>
HTML;

        return $this->formCard('Cấu hình Tên miền Chi tiết', $this->formOpen($action, $method) . $formContent . $this->formClose());
    }
}
