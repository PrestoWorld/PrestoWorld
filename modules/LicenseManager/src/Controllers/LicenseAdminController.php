<?php

declare(strict_types=1);

namespace Modules\LicenseManager\Controllers;

use App\Foundation\Admin\AdminController;
use Modules\LicenseManager\Admin\LicenseTable;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class LicenseAdminController extends AdminController
{
    public function index(Request $request): Response
    {
        $table = (new LicenseTable($this->db()))->prepare($request);
        return $this->htmlResponse(
            $this->adminPage('License Manager', $table->render(), [
                'new_url' => '/dashboard/licenses/create',
                'new_label' => '+ Issue License',
            ])
        );
    }

    public function create(Request $request): Response
    {
        $products = $this->db()->select('id', 'name', 'type')->from('optilarity_software_products')->where('status', 'active')->orderBy('name', 'ASC')->fetchAll();
        $form = $this->renderForm([], '/dashboard/licenses/create', 'POST', $products);
        return $this->htmlResponse(
            $this->adminPage('Issue License', $form, ['breadcrumbs' => ['Licenses' => '/dashboard/licenses', 'Issue' => '']])
        );
    }

    public function store(Request $request): Response
    {
        $body = (array)$request->post();
        try {
            $key = implode('-', array_map(fn() => strtoupper(bin2hex(random_bytes(4))), range(1, 4)));
            $this->db()->insert('optilarity_licenses')->values([
                'license_key'      => $key,
                'license_type'     => $body['license_type'] ?? 'optilarity',
                'license_mode'     => $body['license_mode'] ?? 'strict',
                'customer_id'      => $body['customer_id']  ? (int)$body['customer_id']  : null,
                'email'            => $body['email']        ?: null,
                'order_id'         => $body['order_id']     ? (int)$body['order_id']     : null,
                'product_id'       => $body['product_id']   ? (int)$body['product_id']   : null,
                'status'           => $body['status']        ?? 'active',
                'max_activations'  => (int)($body['max_activations'] ?? 1),
                'activations_used' => 0,
                'expires_at'       => $body['expires_at']   ?: null,
                'created_at'       => date('Y-m-d H:i:s'),
            ])->run();
            return $this->redirect('/dashboard/licenses');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Issue License', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, '/dashboard/licenses/create')));
        }
    }

    public function edit(Request $request, int $id): Response
    {
        $row      = $this->db()->select('*')->from('optilarity_licenses')->where('id', $id)->run()->fetch();
        $products = $this->db()->select('id', 'name')->from('optilarity_software_products')->fetchAll();
        if (!$row) { return $this->htmlResponse($this->adminPage('Not Found', $this->notice('License not found.', 'error')), 404); }
        return $this->htmlResponse(
            $this->adminPage('Edit License', $this->renderForm((array)$row, "/dashboard/licenses/{$id}/edit", 'PUT', $products), ['breadcrumbs' => ['Licenses' => '/dashboard/licenses', 'Edit' => '']])
        );
    }

    public function update(Request $request, int $id): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->update('optilarity_licenses', [
                'license_type'    => $body['license_type']     ?? 'optilarity',
                'license_mode'    => $body['license_mode']     ?? 'strict',
                'email'           => $body['email']            ?: null,
                'status'          => $body['status']           ?? 'active',
                'max_activations' => (int)($body['max_activations'] ?? 1),
                'expires_at'      => $body['expires_at']       ?: null,
                'updated_at'      => date('Y-m-d H:i:s'),
            ], ['id' => $id]);
            return $this->redirect('/dashboard/licenses');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Edit License', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, "/dashboard/licenses/{$id}/edit", 'PUT')));
        }
    }

    private function renderForm(array $data = [], string $action = '', string $method = 'POST', array $products = []): string
    {
        $statusOpts   = ['active' => 'Active', 'expired' => 'Expired', 'suspended' => 'Suspended', 'revoked' => 'Revoked'];
        $typeOpts     = ['optilarity' => 'Optilarity', 'envato' => 'Envato', 'templatemonster' => 'TemplateMonster'];
        $modeOpts     = ['strict' => 'Strict (1 domain)', 'share' => 'Share (multiple domains)'];
        $productOpts  = ['' => '— No Product —'] + array_column($products, 'name', 'id');

        $fields = $this->fieldGroup('License Type',     $this->select('license_type', $typeOpts, $data['license_type'] ?? 'optilarity'))
                . $this->fieldGroup('License Mode',     $this->select('license_mode', $modeOpts, $data['license_mode'] ?? 'strict'))
                . $this->fieldGroup('Customer Email',   $this->input('email', 'email', $data['email'] ?? ''), 'Required for third-party or if customer ID is missing.')
                . $this->fieldGroup('Customer ID',      $this->input('customer_id', 'number', $data['customer_id'] ?? ''), 'Link to local customer account.')
                . $this->fieldGroup('Order ID',         $this->input('order_id',    'number', $data['order_id']    ?? ''))
                . $this->fieldGroup('Product',          $this->select('product_id', $productOpts, $data['product_id'] ?? ''))
                . $this->fieldGroup('Status',           $this->select('status', $statusOpts, $data['status'] ?? 'active'))
                . $this->fieldGroup('Max Activations',  $this->input('max_activations', 'number', $data['max_activations'] ?? 1), 'Only for Share mode.')
                . $this->fieldGroup('Expires At',       $this->input('expires_at', 'date', $data['expires_at'] ?? ''), 'Leave blank for lifetime license.')
                . $this->submitBar('Save License', '/dashboard/licenses');

        return $this->formCard('License Details', $this->formOpen($action, $method) . $fields . $this->formClose());
    }
}
