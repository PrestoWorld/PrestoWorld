<?php

declare(strict_types=1);

namespace Modules\SoftwareCatalog\Controllers;

use App\Foundation\Admin\AdminController;
use Modules\SoftwareCatalog\Admin\SoftwareCatalogTable;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class SoftwareCatalogAdminController extends AdminController
{
    public function index(Request $request): Response
    {
        $table = (new SoftwareCatalogTable($this->db()))->prepare($request);
        return $this->htmlResponse(
            $this->adminPage('Software Catalog', $table->render(), [
                'new_url' => '/dashboard/catalog/create',
                'new_label' => '+ Add Product',
            ])
        );
    }

    public function create(Request $request): Response
    {
        $form = $this->renderForm([], '/dashboard/catalog/create');
        return $this->htmlResponse(
            $this->adminPage('Add Product', $form, ['breadcrumbs' => ['Catalog' => '/dashboard/catalog', 'Add' => '']])
        );
    }

    public function store(Request $request): Response
    {
        $body = (array)$request->post();
        try {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $body['name'] ?? 'product')) . '-' . time();
            $this->db()->insert('optilarity_software_products')->values([
                'type'         => $body['type']        ?? 'software',
                'name'         => $body['name']        ?? '',
                'slug'         => $slug,
                'description'  => $body['description'] ?? null,
                'version'      => $body['version']     ?? null,
                'author'       => $body['author']      ?? null,
                'homepage_url' => $body['homepage_url']?? null,
                'download_url' => $body['download_url']?? null,
                'price'        => (float)($body['price'] ?? 0),
                'currency'     => $body['currency']    ?? 'USD',
                'status'       => $body['status']      ?? 'active',
                'created_at'   => date('Y-m-d H:i:s'),
            ])->run();
            return $this->redirect('/dashboard/catalog');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Add Product', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, '/dashboard/catalog/create')));
        }
    }

    public function edit(Request $request, int $id): Response
    {
        $row = $this->db()->select('*')->from('optilarity_software_products')->where('id', $id)->run()->fetch();
        if (!$row) { return $this->htmlResponse($this->adminPage('Not Found', $this->notice('Product not found.', 'error')), 404); }
        return $this->htmlResponse(
            $this->adminPage('Edit: ' . $row['name'], $this->renderForm((array)$row, "/dashboard/catalog/{$id}/edit", 'PUT'), ['breadcrumbs' => ['Catalog' => '/dashboard/catalog', 'Edit' => '']])
        );
    }

    public function update(Request $request, int $id): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->update('optilarity_software_products', [
                'type'         => $body['type']         ?? 'software',
                'name'         => $body['name']         ?? '',
                'description'  => $body['description']  ?? null,
                'version'      => $body['version']      ?? null,
                'author'       => $body['author']       ?? null,
                'homepage_url' => $body['homepage_url'] ?? null,
                'download_url' => $body['download_url'] ?? null,
                'price'        => (float)($body['price'] ?? 0),
                'currency'     => $body['currency']     ?? 'USD',
                'status'       => $body['status']       ?? 'active',
                'updated_at'   => date('Y-m-d H:i:s'),
            ], ['id' => $id]);
            return $this->redirect('/dashboard/catalog');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Edit Product', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, "/dashboard/catalog/{$id}/edit", 'PUT')));
        }
    }

    private function renderForm(array $data = [], string $action = '', string $method = 'POST'): string
    {
        $typeOpts     = ['software' => 'Software', 'plugin' => 'Plugin', 'theme' => 'Theme'];
        $statusOpts   = ['active' => 'Active', 'draft' => 'Draft', 'deprecated' => 'Deprecated', 'archived' => 'Archived'];
        $currencyOpts = ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'VND' => 'VND'];

        $fields = $this->fieldGroup('Type *',          $this->select('type', $typeOpts, $data['type'] ?? 'software'))
                . $this->fieldGroup('Product Name *',  $this->input('name', 'text', $data['name'] ?? '', '', true))
                . $this->fieldGroup('Description',     $this->textarea('description', $data['description'] ?? ''))
                . $this->fieldGroup('Version',         $this->input('version',      'text', $data['version']      ?? '', 'e.g. 1.2.0'))
                . $this->fieldGroup('Author',          $this->input('author',       'text', $data['author']       ?? ''))
                . $this->fieldGroup('Homepage URL',    $this->input('homepage_url', 'url',  $data['homepage_url'] ?? ''))
                . $this->fieldGroup('Download URL',    $this->input('download_url', 'url',  $data['download_url'] ?? ''))
                . $this->fieldGroup('Price',           $this->input('price', 'number', $data['price'] ?? 0), 'Set 0 for free products.')
                . $this->fieldGroup('Currency',        $this->select('currency', $currencyOpts, $data['currency'] ?? 'USD'))
                . $this->fieldGroup('Status',          $this->select('status', $statusOpts, $data['status'] ?? 'active'))
                . $this->submitBar('Save Product', '/dashboard/catalog');

        return $this->formCard('Product Details', $this->formOpen($action, $method) . $fields . $this->formClose());
    }
}
