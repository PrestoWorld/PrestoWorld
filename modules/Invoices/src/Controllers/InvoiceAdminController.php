<?php

declare(strict_types=1);

namespace Modules\Invoices\Controllers;

use App\Foundation\Admin\AdminController;
use Modules\Invoices\Admin\InvoiceTable;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class InvoiceAdminController extends AdminController
{
    public function index(Request $request): Response
    {
        $table = (new InvoiceTable($this->db()))->prepare($request);
        return $this->htmlResponse(
            $this->adminPage('Invoices', $table->render(), [
                'new_url' => '/dashboard/invoices/create',
                'new_label' => '+ New Invoice',
            ])
        );
    }

    public function create(Request $request): Response
    {
        $form = $this->renderForm([], '/dashboard/invoices/create');
        return $this->htmlResponse(
            $this->adminPage('New Invoice', $form, ['breadcrumbs' => ['Invoices' => '/dashboard/invoices', 'New' => '']])
        );
    }

    public function store(Request $request): Response
    {
        $body = (array)$request->post();
        try {
            $inv = 'INV-' . date('Y') . '-' . str_pad((string)rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $this->db()->insert('optilarity_invoices')->values([
                'invoice_number' => $inv,
                'customer_email' => $body['customer_email'] ?? null,
                'customer_name'  => $body['customer_name']  ?? null,
                'status'         => $body['status']          ?? 'draft',
                'subtotal'       => (float)($body['subtotal'] ?? 0),
                'tax'            => (float)($body['tax']      ?? 0),
                'total'          => (float)($body['total']    ?? 0),
                'currency'       => $body['currency']         ?? 'USD',
                'due_date'       => $body['due_date']         ?? null,
                'notes'          => $body['notes']            ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
            ])->run();
            return $this->redirect('/dashboard/invoices');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('New Invoice', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, '/dashboard/invoices/create')));
        }
    }

    public function edit(Request $request, int $id): Response
    {
        $row = $this->db()->select('*')->from('optilarity_invoices')->where('id', $id)->run()->fetch();
        if (!$row) { return $this->htmlResponse($this->adminPage('Not Found', $this->notice('Invoice not found.', 'error')), 404); }
        return $this->htmlResponse(
            $this->adminPage('Edit Invoice ' . $row['invoice_number'], $this->renderForm((array)$row, "/dashboard/invoices/{$id}/edit", 'PUT'), ['breadcrumbs' => ['Invoices' => '/dashboard/invoices', 'Edit' => '']])
        );
    }

    public function update(Request $request, int $id): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->update('optilarity_invoices', [
                'status'   => $body['status']   ?? 'draft',
                'subtotal' => (float)($body['subtotal'] ?? 0),
                'tax'      => (float)($body['tax']      ?? 0),
                'total'    => (float)($body['total']    ?? 0),
                'currency' => $body['currency']  ?? 'USD',
                'due_date' => $body['due_date']  ?? null,
                'notes'    => $body['notes']     ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id]);
            return $this->redirect('/dashboard/invoices');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Edit Invoice', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, "/dashboard/invoices/{$id}/edit", 'PUT')));
        }
    }

    private function renderForm(array $data = [], string $action = '', string $method = 'POST'): string
    {
        $statusOptions  = ['draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'];
        $currencyOpts   = ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'VND' => 'VND'];

        $fields = $this->fieldGroup('Customer Name',  $this->input('customer_name',  'text',  $data['customer_name']  ?? ''))
                . $this->fieldGroup('Customer Email', $this->input('customer_email', 'email', $data['customer_email'] ?? ''))
                . $this->fieldGroup('Status',         $this->select('status', $statusOptions, $data['status'] ?? 'draft'))
                . $this->fieldGroup('Subtotal',       $this->input('subtotal', 'number', $data['subtotal'] ?? 0))
                . $this->fieldGroup('Tax',            $this->input('tax',      'number', $data['tax']      ?? 0))
                . $this->fieldGroup('Total',          $this->input('total',    'number', $data['total']    ?? 0))
                . $this->fieldGroup('Currency',       $this->select('currency', $currencyOpts, $data['currency'] ?? 'USD'))
                . $this->fieldGroup('Due Date',       $this->input('due_date', 'date', $data['due_date'] ?? ''))
                . $this->fieldGroup('Notes',          $this->textarea('notes', $data['notes'] ?? ''))
                . $this->submitBar('Save Invoice', '/dashboard/invoices');

        return $this->formCard('Invoice Details', $this->formOpen($action, $method) . $fields . $this->formClose());
    }
}
