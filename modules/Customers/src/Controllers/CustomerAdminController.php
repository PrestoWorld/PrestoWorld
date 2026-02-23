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
        $fields = $this->fieldGroup('First Name *',    $this->input('first_name', 'text', $data['first_name'] ?? '', 'John', true))
                . $this->fieldGroup('Last Name *',     $this->input('last_name',  'text', $data['last_name']  ?? '', 'Doe',  true))
                . $this->fieldGroup('Email Address *', $this->input('email',      'email',$data['email']      ?? '', 'john@example.com', true))
                . $this->fieldGroup('Phone',           $this->input('phone',      'tel',  $data['phone']      ?? ''))
                . $this->fieldGroup('Company',         $this->input('company',    'text', $data['company']    ?? ''))
                . $this->fieldGroup('Country',         $this->input('country',    'text', $data['country']    ?? ''))
                . $this->fieldGroup('Address',         $this->textarea('address', $data['address'] ?? '', '', 3))
                . $this->fieldGroup('Status',          $this->select('status', $statusOptions, $data['status'] ?? 'active'))
                . $this->fieldGroup('Notes',           $this->textarea('notes', $data['notes'] ?? ''))
                . $this->submitBar('Save Customer', '/dashboard/customers');

        return $this->formCard('Customer Details', $this->formOpen($action, $method) . $fields . $this->formClose());
    }
}
