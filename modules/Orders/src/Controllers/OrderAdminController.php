<?php

declare(strict_types=1);

namespace Modules\Orders\Controllers;

use App\Foundation\Admin\AdminController;
use Modules\Orders\Admin\OrderTable;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class OrderAdminController extends AdminController
{
    private function table(): OrderTable
    {
        return new OrderTable($this->db());
    }

    /** GET /dashboard/orders */
    public function index(Request $request): Response
    {
        $table   = $this->table()->prepare($request);
        $content = $table->render();
        return $this->htmlResponse(
            $this->adminPage('Orders', $content, [
                'new_url' => '/dashboard/orders/create',
                'new_label' => '+ New Order',
            ])
        );
    }

    /** GET /dashboard/orders/{id} */
    public function show(Request $request, int $id): Response
    {
        $db = $this->db();
        $order = $db->select('*')->from('optilarity_orders')->where('id', $id)->run()->fetch();
        if (!$order) {
            return $this->htmlResponse($this->adminPage('Not Found', $this->notice('Order not found.', 'error')), 404);
        }

        // 1. Fetch Related Hosting
        $hostings = $db->select('h.*', 'p.name AS plan_name')
            ->from('optilarity_hosting_orders AS ho')
            ->innerJoin('optilarity_hostings AS h')->on('ho.hosting_id', 'h.id')
            ->leftJoin('optilarity_hosting_plans AS p')->on('h.plan_id', 'p.id')
            ->where('ho.order_id', $id)
            ->run()->fetchAll();

        // 2. Fetch Related Domains
        $domains = $db->select('d.*')
            ->from('optilarity_domain_orders AS do')
            ->innerJoin('optilarity_domains AS d')->on('do.domain_id', 'd.id')
            ->where('do.order_id', $id)
            ->run()->fetchAll();

        // 3. Fetch Related SSL
        $ssls = $db->select('s.*')
            ->from('optilarity_ssl_orders AS so')
            ->innerJoin('optilarity_ssl_certificates AS s')->on('so.ssl_id', 's.id')
            ->where('so.order_id', $id)
            ->run()->fetchAll();

        // 4. Fetch Related Email
        $emails = $db->select('e.*')
            ->from('optilarity_email_orders AS eo')
            ->innerJoin('optilarity_email_hosting AS e')->on('eo.email_id', 'e.id')
            ->where('eo.order_id', $id)
            ->run()->fetchAll();

        // 5. Fetch Related Software
        $softwares = $db->select('s.*', 'so.license_key')
            ->from('optilarity_software_orders AS so')
            ->innerJoin('optilarity_software_products AS s')->on('so.product_id', 's.id')
            ->where('so.order_id', $id)
            ->run()->fetchAll();

        $itemsHtml = '';
        if (empty($hostings) && empty($domains) && empty($ssls) && empty($emails) && empty($softwares)) {
            $itemsHtml = '<p class="text-dim">Chưa có dịch vụ nào được gán cho đơn hàng này.</p>';
        } else {
            foreach ($hostings as $h) {
                $itemsHtml .= $this->renderServiceItem('🖥️ Hosting', $h['domain'], "Gói: {$h['plan_name']}", $h['status'], $h['expiry_date']);
            }
            foreach ($domains as $d) {
                $itemsHtml .= $this->renderServiceItem('🌐 Domain', $d['domain_name'], "Registrar: {$d['registrar']}", $d['status'], $d['expiry_date']);
            }
            foreach ($ssls as $s) {
                $itemsHtml .= $this->renderServiceItem('🔒 SSL', $s['domain'], "Provider: {$s['provider']} ({$s['type']})", $s['status'], $s['expiry_date']);
            }
            foreach ($emails as $e) {
                $itemsHtml .= $this->renderServiceItem('📧 Email', $e['domain'], "Gói: {$e['plan_name']} ({$e['mailbox_count']} mailboxes)", $e['status']);
            }
            foreach ($softwares as $s) {
                $license = $s['license_key'] ? "License: <code style='background:rgba(255,255,255,0.1);padding:2px 6px;border-radius:4px;'>{$s['license_key']}</code>" : "Bản quyền vĩnh viễn";
                $itemsHtml .= $this->renderServiceItem('💻 ' . ucfirst($s['type']), $s['name'], $license, $s['status']);
            }
        }

        $content = <<<HTML
        <div class="presto-card mb-32">
            <div class="presto-card-header">
                <h2 class="presto-card-title">Order #{$order['order_number']}</h2>
                <div class="card-actions">
                    <a href="/dashboard/orders/{id}/edit" class="presto-btn presto-btn-secondary">Edit Order</a>
                </div>
            </div>
            <div class="presto-card-body">
                <div class="dashboard-bottom-row" style="margin-top:0; grid-template-columns: 1fr 1fr;">
                    <div>
                        <h3 class="section-title" style="margin-top:0;">Dữ liệu đơn hàng</h3>
                        <p><strong>Ngày tạo:</strong> {$order['created_at']}</p>
                        <p><strong>Trạng thái:</strong> <span class="badge badge-{$order['status']}">{$order['status']}</span></p>
                        <p><strong>Thanh toán:</strong> <span class="badge badge-{$order['payment_status']}">{$order['payment_status']}</span></p>
                        <p><strong>Phương thức:</strong> {$order['payment_method']}</p>
                    </div>
                    <div>
                        <h3 class="section-title" style="margin-top:0;">Khách hàng & Tổng tiền</h3>
                        <p><strong>Email:</strong> {$order['customer_email']}</p>
                        <p><strong>ID Khách:</strong> #{$order['customer_id']}</p>
                        <p><strong>Tổng cộng:</strong> <strong style="font-size: 24px; color: var(--primary);">{$order['total']} {$order['currency']}</strong></p>
                        <p><strong>Mã giao dịch:</strong> {$order['transaction_id']}</p>
                    </div>
                </div>

                <div style="margin-top: 48px; border-top: 1px solid var(--border); padding-top: 32px;">
                    <h3 class="section-title" style="margin-top:0; margin-bottom: 24px;">Sản phẩm & Dịch vụ đã mua</h3>
                    <div class="order-services-list">
                        {$itemsHtml}
                    </div>
                </div>

                <div style="margin-top: 48px; border-top: 1px solid var(--border); padding-top: 32px;">
                    <h3 class="section-title" style="margin-top:0;">Ghi chú</h3>
                    <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; min-height: 80px; font-style: italic;">
                        {$order['notes']}
                    </div>
                </div>
            </div>
        </div>

        <style>
            .order-services-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
            .service-item-card { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 16px; padding: 20px; transition: 0.3s; }
            .service-item-card:hover { background: rgba(255,255,255,0.06); border-color: var(--primary); transform: translateY(-3px); }
            .service-type { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.1em; margin-bottom: 8px; display: block; }
            .service-main { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 4px; display: block; }
            .service-sub { font-size: 13px; color: var(--text-dim); margin-bottom: 12px; display: block; }
            .service-meta { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 12px; }
            .service-expiry { font-size: 12px; color: var(--warning); font-weight: 600; }
        </style>
HTML;

        return $this->htmlResponse($this->adminPage('Order Detail', $content, [
            'breadcrumbs' => ['Orders' => '/dashboard/orders', 'Detail' => '']
        ]));
    }

    private function renderServiceItem(string $type, string $main, string $sub, string $status, ?string $expiry = null): string
    {
        $expiryHtml = $expiry ? "<span class='service-expiry'>Hết hạn: " . date('d/m/Y', strtotime($expiry)) . "</span>" : "";
        return <<<HTML
        <div class="service-item-card">
            <span class="service-type">{$type}</span>
            <span class="service-main">{$main}</span>
            <span class="service-sub">{$sub}</span>
            <div class="service-meta">
                <span class="badge badge-{$status}">{$status}</span>
                {$expiryHtml}
            </div>
        </div>
HTML;
    }

    /** GET /dashboard/orders/create */
    public function create(Request $request): Response
    {
        $plans = $this->db()->select('id', 'name')->from('optilarity_hosting_plans')->run()->fetchAll();
        $softwares = $this->db()->select('id', 'name', 'type')->from('optilarity_software_products')->run()->fetchAll();
        
        $form = $this->renderForm([], '/dashboard/orders/create', 'POST', $plans, $softwares);
        return $this->htmlResponse(
            $this->adminPage('New Order', $form, ['breadcrumbs' => ['Orders' => '/dashboard/orders', 'New Order' => '']])
        );
    }

    /** POST /dashboard/orders/create */
    public function store(Request $request): Response
    {
        $body = (array)$request->post();
        $db = $this->db();
        
        try {
            // 1. Create Order
            $orderId = $db->insert('optilarity_orders')->values([
                'order_number'   => 'ORD-' . strtoupper(uniqid()),
                'customer_email' => $body['customer_email'] ?? '',
                'customer_id'    => $body['customer_id']    ? (int)$body['customer_id'] : null,
                'status'         => $body['status']          ?? 'pending',
                'payment_status' => $body['payment_status']  ?? 'pending',
                'payment_method' => $body['payment_method']  ?? null,
                'subtotal'       => (float)($body['subtotal'] ?? 0),
                'tax'            => (float)($body['tax']      ?? 0),
                'total'          => (float)($body['total']    ?? 0),
                'currency'       => $body['currency']         ?? 'USD',
                'notes'          => $body['notes']            ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
            ])->run()->lastInsertID();

            // 2. Handle Hosting Attachment
            if (!empty($body['hosting_plan_id']) && !empty($body['hosting_domain'])) {
                $hostingId = $db->insert('optilarity_hostings')->values([
                    'customer_id' => $body['customer_id'] ? (int)$body['customer_id'] : 0,
                    'plan_id'     => (int)$body['hosting_plan_id'],
                    'domain'      => $body['hosting_domain'],
                    'status'      => 'pending',
                    'created_at'  => date('Y-m-d H:i:s'),
                ])->run()->lastInsertID();

                $db->insert('optilarity_hosting_orders')->values([
                    'hosting_id' => $hostingId,
                    'order_id'   => $orderId,
                    'created_at' => date('Y-m-d H:i:s'),
                ])->run();
            }

            // 3. Handle Software products attachment
            if (!empty($body['software_ids']) && is_array($body['software_ids'])) {
                foreach ($body['software_ids'] as $pid) {
                    $db->insert('optilarity_software_orders')->values([
                        'product_id' => (int)$pid,
                        'order_id'   => $orderId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ])->run();
                }
            }

            return $this->redirect('/dashboard/orders');
        } catch (\Throwable $e) {
            $plans = $db->select('id', 'name')->from('optilarity_hosting_plans')->run()->fetchAll();
            $softwares = $db->select('id', 'name', 'type')->from('optilarity_software_products')->run()->fetchAll();
            $form = $this->notice("Error: {$e->getMessage()}", 'error') . $this->renderForm($body, '/dashboard/orders/create', 'POST', $plans, $softwares);
            return $this->htmlResponse($this->adminPage('New Order', $form, ['breadcrumbs' => ['Orders' => '/dashboard/orders', 'New Order' => '']]));
        }
    }

    /** GET /dashboard/orders/{id}/edit */
    public function edit(Request $request, int $id): Response
    {
        $row = $this->db()->select('*')->from('optilarity_orders')->where('id', $id)->run()->fetch();
        if (!$row) {
            return $this->htmlResponse($this->adminPage('Not Found', $this->notice('Order not found.', 'error')), 404);
        }
        
        $plans = $this->db()->select('id', 'name')->from('optilarity_hosting_plans')->run()->fetchAll();
        $softwares = $this->db()->select('id', 'name', 'type')->from('optilarity_software_products')->run()->fetchAll();

        $form = $this->renderForm((array)$row, "/dashboard/orders/{$id}/edit", 'PUT', $plans, $softwares);
        return $this->htmlResponse(
            $this->adminPage('Edit Order #' . $row['order_number'], $form, ['breadcrumbs' => ['Orders' => '/dashboard/orders', 'Edit' => '']])
        );
    }

    /** PUT /dashboard/orders/{id}/edit */
    public function update(Request $request, int $id): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->update('optilarity_orders', [
                'status'         => $body['status']          ?? 'pending',
                'payment_status' => $body['payment_status']  ?? 'pending',
                'payment_method' => $body['payment_method']  ?? null,
                'transaction_id' => $body['transaction_id']  ?? null,
                'notes'          => $body['notes']           ?? null,
                'updated_at'     => date('Y-m-d H:i:s'),
            ], ['id' => $id]);
            return $this->redirect('/dashboard/orders');
        } catch (\Throwable $e) {
            $plans = $this->db()->select('id', 'name')->from('optilarity_hosting_plans')->run()->fetchAll();
            $softwares = $this->db()->select('id', 'name', 'type')->from('optilarity_software_products')->run()->fetchAll();
            $form = $this->notice("Error: {$e->getMessage()}", 'error') . $this->renderForm($body, "/dashboard/orders/{$id}/edit", 'PUT', $plans, $softwares);
            return $this->htmlResponse($this->adminPage('Edit Order', $form));
        }
    }

    private function renderForm(array $data = [], string $action = '', string $method = 'POST', array $plans = [], array $softwares = []): string
    {
        $statusOptions  = ['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'];
        $payStatusOpts  = ['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'];
        $currencyOpts   = ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'VND' => 'VND'];

        $planOpts = ['' => '-- Không gán hosting --'];
        foreach ($plans as $p) $planOpts[$p['id']] = $p['name'];

        $swListHtml = '<div class="presto-check-list">';
        foreach ($softwares as $s) {
            $checked = (isset($data['software_ids']) && in_array($s['id'], (array)$data['software_ids'])) ? 'checked' : '';
            $swListHtml .= <<<HTML
            <label class="presto-check-item">
                <input type='checkbox' name='software_ids[]' value='{$s['id']}' {$checked}>
                <span class="presto-check-label">{$s['name']}</span>
                <span class="presto-check-badge">{$s['type']}</span>
            </label>
HTML;
        }
        $swListHtml .= '</div>';

        $formContent = <<<HTML
        <div class="presto-form-section-head">
            <div class="icon-wrap">👤</div>
            <h3>Thông tin Đơn hàng & Khách hàng</h3>
        </div>
        <div class="presto-grid">
            <div class="col-8">{$this->fieldGroup('Địa chỉ Email Khách hàng', $this->input('customer_email', 'email', $data['customer_email'] ?? '', 'client@example.com', true))}</div>
            <div class="col-4">{$this->fieldGroup('ID Khách hàng (Nếu có)', $this->input('customer_id', 'number', $data['customer_id'] ?? ''))}</div>
            
            <div class="col-6">{$this->fieldGroup('Trạng thái Đơn hàng', $this->select('status', $statusOptions, $data['status'] ?? 'pending'))}</div>
            <div class="col-6">{$this->fieldGroup('Trạng thái Thanh toán', $this->select('payment_status', $payStatusOpts, $data['payment_status'] ?? 'pending'))}</div>
        </div>

        <div class="presto-form-section-head">
            <div class="icon-wrap">💰</div>
            <h3>Tài chính & Ghi chú</h3>
        </div>
        <div class="presto-grid">
            <div class="col-4">{$this->fieldGroup('Tổng tiền thanh toán', $this->input('total', 'number', $data['total'] ?? 0))}</div>
            <div class="col-4">{$this->fieldGroup('Đơn vị tiền tệ', $this->select('currency', $currencyOpts, $data['currency'] ?? 'USD'))}</div>
            <div class="col-4">{$this->fieldGroup('Phương thức TT', $this->input('payment_method', 'text', $data['payment_method'] ?? '', 'PayPal, Bank Transfer...'))}</div>
            
            <div class="col-12">{$this->fieldGroup('Ghi chú nội bộ (Chỉ Admin thấy)', $this->textarea('notes', $data['notes'] ?? '', 'Ghi chú về đơn hàng, lý do giảm giá...'))}</div>
        </div>

        <div class="presto-form-section-head">
            <div class="icon-wrap">🔌</div>
            <h3>Gắn Sản phẩm & Dịch vụ đi kèm</h3>
        </div>
        
        <div class="presto-grid">
            <div class="col-6">
                {$this->fieldGroup('Gắn nhanh Hosting Plan', $this->select('hosting_plan_id', $planOpts, $data['hosting_plan_id'] ?? ''))}
            </div>
            <div class="col-6">
                {$this->fieldGroup('Tên miền đi kèm Hosting', $this->input('hosting_domain', 'text', $data['hosting_domain'] ?? '', 'example.com'))}
            </div>
            <div class="col-12">
                <label class="presto-field-label">Chọn Phần mềm / Plugins / Themes</label>
                {$swListHtml}
            </div>
        </div>

        <div style="margin-top: 48px; display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid var(--border); padding-top: 32px;">
            <a href="/dashboard/orders" class="presto-btn presto-btn-secondary">Hủy bỏ</a>
            <button type="submit" class="presto-btn presto-btn-primary">Lưu Đơn Hàng Ngay</button>
        </div>
HTML;

        return $this->formCard('Cấu hình Đơn hàng Chi tiết', $this->formOpen($action, $method) . $formContent . $this->formClose());
    }
}
