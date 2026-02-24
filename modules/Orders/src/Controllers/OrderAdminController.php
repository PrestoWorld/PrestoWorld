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
        $order = $this->db()->select('*')->from('optilarity_orders')->where('id', $id)->run()->fetch();
        if (!$order) {
            return $this->htmlResponse($this->adminPage('Not Found', $this->notice('Order not found.', 'error')), 404);
        }

        $content = <<<HTML
        <div class="presto-card mb-32">
            <div class="presto-card-header">
                <h2 class="presto-card-title">Order #{$order['order_number']}</h2>
                <div class="card-actions">
                    <a href="/dashboard/orders/{$id}/edit" class="presto-btn presto-btn-secondary">Edit Order</a>
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
                        <p><strong>Tổng cộng:</strong> <strong style="font-size: 24px;">{$order['total']} {$order['currency']}</strong></p>
                        <p><strong>Mã giao dịch:</strong> {$order['transaction_id']}</p>
                    </div>
                </div>
                <div style="margin-top: 40px; border-top: 1px solid var(--border); padding-top: 24px;">
                    <h3 class="section-title" style="margin-top:0;">Ghi chú</h3>
                    <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; min-height: 100px;">
                        {$order['notes']}
                    </div>
                </div>
            </div>
        </div>
HTML;

        return $this->htmlResponse($this->adminPage('Order Detail', $content, [
            'breadcrumbs' => ['Orders' => '/dashboard/orders', 'Detail' => '']
        ]));
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
