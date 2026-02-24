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

        $formContent = <<<HTML
        <div class="presto-form-section-head">
            <div class="icon-wrap">📦</div>
            <h3>Thông tin Sản phẩm chính</h3>
        </div>
        <div class="presto-grid">
            <div class="col-8">{$this->fieldGroup('Tên sản phẩm *', $this->input('name', 'text', $data['name'] ?? '', 'Product name', true))}</div>
            <div class="col-4">{$this->fieldGroup('Loại (Type) *', $this->select('type', $typeOpts, $data['type'] ?? 'software'))}</div>
            
            <div class="col-12">{$this->fieldGroup('Mô tả sản phẩm', $this->textarea('description', $data['description'] ?? '', 'Chi tiết về tính năng...'))}</div>
            
            <div class="col-4">{$this->fieldGroup('Phiên bản', $this->input('version', 'text', $data['version'] ?? '', '1.0.0'))}</div>
            <div class="col-4">{$this->fieldGroup('Tác giả/Nhà phát triển', $this->input('author', 'text', $data['author'] ?? ''))}</div>
            <div class="col-4">{$this->fieldGroup('Trạng thái hiển thị', $this->select('status', $statusOpts, $data['status'] ?? 'active'))}</div>
        </div>

        <div class="presto-form-section-head">
            <div class="icon-wrap">🔗</div>
            <h3>Liên kết & Tải về</h3>
        </div>
        <div class="presto-grid">
            <div class="col-6">{$this->fieldGroup('URL Trang chủ', $this->input('homepage_url', 'url', $data['homepage_url'] ?? ''))}</div>
            <div class="col-6">{$this->fieldGroup('URL Tải về / Source', $this->input('download_url', 'url', $data['download_url'] ?? ''))}</div>
        </div>

        <div class="presto-form-section-head">
            <div class="icon-wrap">💰</div>
            <h3>Giá cả & Thương mại</h3>
        </div>
        <div class="presto-grid">
            <div class="col-6">{$this->fieldGroup('Giá bán', $this->input('price', 'number', $data['price'] ?? 0))}</div>
            <div class="col-6">{$this->fieldGroup('Tiền tệ', $this->select('currency', $currencyOpts, $data['currency'] ?? 'USD'))}</div>
        </div>

        <div style="margin-top: 48px; display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid var(--border); padding-top: 32px;">
            <a href="/dashboard/catalog" class="presto-btn presto-btn-secondary">Hủy bỏ</a>
            <button type="submit" class="presto-btn presto-btn-primary">Lưu Sản Phẩm</button>
        </div>
HTML;

        return $this->formCard('Cấu hình Sản phẩm Số', $this->formOpen($action, $method) . $formContent . $this->formClose());
    }
}
