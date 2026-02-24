<?php

declare(strict_types=1);

namespace Modules\Customers\Controllers;

use App\Foundation\Admin\AdminController;
use Modules\Customers\Admin\CustomerTable;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class CustomerAdminController extends AdminController
{
    private function table(): CustomerTable
    {
        return new CustomerTable($this->db());
    }

    /** GET /dashboard/customers */
    public function index(Request $request): Response
    {
        $table   = $this->table()->prepare($request);
        $content = $table->render();

        return $this->htmlResponse(
            $this->adminPage('Customers', $content, [
                'new_url' => '/dashboard/customers/create',
                'new_label' => '+ Add Customer',
            ])
        );
    }

    /** GET /dashboard/customers/create */
    public function create(Request $request): Response
    {
        $form = $this->renderForm([], '/dashboard/customers/create');
        return $this->htmlResponse(
            $this->adminPage('Add Customer', $form, [
                'breadcrumbs' => ['Customers' => '/dashboard/customers', 'Add New' => ''],
            ])
        );
    }

    /** POST /dashboard/customers/create */
    public function store(Request $request): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->insert('optilarity_customers')->values([
                'first_name' => $body['first_name'] ?? '',
                'last_name'  => $body['last_name']  ?? '',
                'email'      => $body['email']       ?? '',
                'phone'      => $body['phone']       ?? null,
                'company'    => $body['company']     ?? null,
                'country'    => $body['country']     ?? null,
                'address'    => $body['address']     ?? null,
                'status'     => $body['status']      ?? 'active',
                'notes'      => $body['notes']       ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ])->run();
            return $this->redirect('/dashboard/customers');
        } catch (\Throwable $e) {
            $form = $this->notice("Error: {$e->getMessage()}", 'error')
                  . $this->renderForm($body, '/dashboard/customers/create');
            return $this->htmlResponse(
                $this->adminPage('Add Customer', $form, ['breadcrumbs' => ['Customers' => '/dashboard/customers', 'Add New' => '']])
            );
        }
    }

    /** GET /dashboard/customers/{id}/edit */
    public function edit(Request $request, int $id): Response
    {
        $row = $this->db()->select('*')->from('optilarity_customers')->where('id', $id)->run()->fetch();
        if (!$row) {
            return $this->htmlResponse($this->adminPage('Not Found', $this->notice('Customer not found.', 'error')), 404);
        }
        $form = $this->renderForm((array)$row, "/dashboard/customers/{$id}/edit", 'PUT');
        return $this->htmlResponse(
            $this->adminPage('Edit Customer', $form, [
                'breadcrumbs' => ['Customers' => '/dashboard/customers', 'Edit' => ''],
            ])
        );
    }

    /** PUT /dashboard/customers/{id}/edit */
    public function update(Request $request, int $id): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->update('optilarity_customers', [
                'first_name' => $body['first_name'] ?? '',
                'last_name'  => $body['last_name']  ?? '',
                'email'      => $body['email']       ?? '',
                'phone'      => $body['phone']       ?? null,
                'company'    => $body['company']     ?? null,
                'country'    => $body['country']     ?? null,
                'address'    => $body['address']     ?? null,
                'status'     => $body['status']      ?? 'active',
                'notes'      => $body['notes']       ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id]);
            return $this->redirect('/dashboard/customers');
        } catch (\Throwable $e) {
            $form = $this->notice("Error: {$e->getMessage()}", 'error')
                  . $this->renderForm($body, "/dashboard/customers/{$id}/edit", 'PUT');
            return $this->htmlResponse(
                $this->adminPage('Edit Customer', $form, ['breadcrumbs' => ['Customers' => '/dashboard/customers', 'Edit' => '']])
            );
        }
    }

    private function renderForm(array $data = [], string $action = '', string $method = 'POST'): string
    {
        $statusOptions = ['active' => 'Active', 'suspended' => 'Suspended', 'banned' => 'Banned'];
        
        $formContent = <<<HTML
        <div class="presto-form-section-head">
            <div class="icon-wrap">🆔</div>
            <h3>Danh tính & Trạng thái</h3>
        </div>
        <div class="presto-grid">
            <div class="col-4">{$this->fieldGroup('Tên (First Name) *', $this->input('first_name', 'text', $data['first_name'] ?? '', 'John', true))}</div>
            <div class="col-4">{$this->fieldGroup('Họ (Last Name) *', $this->input('last_name', 'text', $data['last_name'] ?? '', 'Doe', true))}</div>
            <div class="col-4">{$this->fieldGroup('Trạng thái tài khoản', $this->select('status', $statusOptions, $data['status'] ?? 'active'))}</div>
            
            <div class="col-8">{$this->fieldGroup('Địa chỉ Email *', $this->input('email', 'email', $data['email'] ?? '', 'john@example.com', true))}</div>
            <div class="col-4">{$this->fieldGroup('Số điện thoại', $this->input('phone', 'tel', $data['phone'] ?? '', '090...'))}</div>
        </div>

        <div class="presto-form-section-head">
            <div class="icon-wrap">🏢</div>
            <h3>Thông tin Công ty & Địa chỉ</h3>
        </div>
        <div class="presto-grid">
            <div class="col-6">{$this->fieldGroup('Tên công ty', $this->input('company', 'text', $data['company'] ?? ''))}</div>
            <div class="col-6">{$this->fieldGroup('Quốc gia', $this->input('country', 'text', $data['country'] ?? ''))}</div>
            <div class="col-12">{$this->fieldGroup('Địa chỉ chi tiết', $this->textarea('address', $data['address'] ?? '', 'Số nhà, tên đường...', 2))}</div>
            
            <div class="col-12">{$this->fieldGroup('Ghi chú về khách hàng', $this->textarea('notes', $data['notes'] ?? ''))}</div>
        </div>

        <div style="margin-top: 48px; display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid var(--border); padding-top: 32px;">
            <a href="/dashboard/customers" class="presto-btn presto-btn-secondary">Hủy bỏ</a>
            <button type="submit" class="presto-btn presto-btn-primary">Lưu Thông Tin Khách Hàng</button>
        </div>
HTML;

        return $this->formCard('Cấu hình Khách hàng', $this->formOpen($action, $method) . $formContent . $this->formClose());
    }
}
