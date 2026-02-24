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
        $hostings = $db->select('h.*', 'p.name as plan_name')
            ->from('optilarity_hosting_orders', 'ho')
            ->innerJoin('optilarity_hostings', 'h')->on('ho.hosting_id', 'h.id')
            ->leftJoin('optilarity_hosting_plans', 'p')->on('h.plan_id', 'p.id')
            ->where('ho.order_id', $id)
            ->run()->fetchAll();

        // 2. Fetch Related Domains
        $domains = $db->select('d.*')
            ->from('optilarity_domain_orders', 'do')
            ->innerJoin('optilarity_domains', 'd')->on('do.domain_id', 'd.id')
            ->where('do.order_id', $id)
            ->run()->fetchAll();

        // 3. Fetch Related SSL
        $ssls = $db->select('s.*')
            ->from('optilarity_ssl_orders', 'so')
            ->innerJoin('optilarity_ssl_certificates', 's')->on('so.ssl_id', 's.id')
            ->where('so.order_id', $id)
            ->run()->fetchAll();

        // 4. Fetch Related Email
        $emails = $db->select('e.*')
            ->from('optilarity_email_orders', 'eo')
            ->innerJoin('optilarity_email_hosting', 'e')->on('eo.email_id', 'e.id')
            ->where('eo.order_id', $id)
            ->run()->fetchAll();

        // 5. Fetch Related Software
        $softwares = $db->select('s.*', 'so.license_key')
            ->from('optilarity_software_orders', 'so')
            ->innerJoin('optilarity_software_products', 's')->on('so.product_id', 's.id')
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
        $form = $this->renderForm([], '/dashboard/orders/create');
        return $this->htmlResponse(
            $this->adminPage('New Order', $form, ['breadcrumbs' => ['Orders' => '/dashboard/orders', 'New Order' => '']])
        );
    }

    /** POST /dashboard/orders/create */
    public function store(Request $request): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->insert('optilarity_orders')->values([
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
            ])->run();
            return $this->redirect('/dashboard/orders');
        } catch (\Throwable $e) {
            $form = $this->notice("Error: {$e->getMessage()}", 'error') . $this->renderForm($body, '/dashboard/orders/create');
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
        $form = $this->renderForm((array)$row, "/dashboard/orders/{$id}/edit", 'PUT');
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
            $form = $this->notice("Error: {$e->getMessage()}", 'error') . $this->renderForm($body, "/dashboard/orders/{$id}/edit", 'PUT');
            return $this->htmlResponse($this->adminPage('Edit Order', $form));
        }
    }

    private function renderForm(array $data = [], string $action = '', string $method = 'POST'): string
    {
        $statusOptions  = ['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'];
        $payStatusOpts  = ['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'];
        $currencyOpts   = ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'VND' => 'VND'];

        $fields = $this->fieldGroup('Customer Email', $this->input('customer_email', 'email', $data['customer_email'] ?? ''))
                . $this->fieldGroup('Status',          $this->select('status',         $statusOptions,  $data['status']          ?? 'pending'))
                . $this->fieldGroup('Payment Status',  $this->select('payment_status', $payStatusOpts,  $data['payment_status']  ?? 'pending'))
                . $this->fieldGroup('Payment Method',  $this->input('payment_method',  'text', $data['payment_method'] ?? ''))
                . $this->fieldGroup('Transaction ID',  $this->input('transaction_id',  'text', $data['transaction_id'] ?? ''))
                . $this->fieldGroup('Subtotal',        $this->input('subtotal', 'number', $data['subtotal'] ?? 0))
                . $this->fieldGroup('Tax',             $this->input('tax',      'number', $data['tax']      ?? 0))
                . $this->fieldGroup('Total',           $this->input('total',    'number', $data['total']    ?? 0))
                . $this->fieldGroup('Currency',        $this->select('currency', $currencyOpts, $data['currency'] ?? 'USD'))
                . $this->fieldGroup('Notes',           $this->textarea('notes', $data['notes'] ?? ''))
                . $this->submitBar('Save Order', '/dashboard/orders');

        return $this->formCard('Order Details', $this->formOpen($action, $method) . $fields . $this->formClose());
    }
}
