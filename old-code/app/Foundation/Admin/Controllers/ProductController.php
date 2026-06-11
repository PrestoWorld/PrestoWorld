<?php

declare(strict_types=1);

namespace App\Foundation\Admin\Controllers;

use App\Foundation\Admin\AdminController;
use App\Services\CategoryService;
use App\Services\ProductService;

/**
 * Product Management Controller
 * 
 * Handles product listing, creation, editing in admin dashboard
 */
class ProductController extends AdminController
{
    private ProductService $productService;
    private CategoryService $categoryService;

    public function __construct(\Witals\Framework\Application $app)
    {
        parent::__construct($app);
        $this->productService = $app->make(ProductService::class);
        $this->categoryService = $app->make(CategoryService::class);
    }

    /**
     * GET /dashboard/products
     * List all products with filtering
     */
    public function index()
    {
        $filters = [
            'status' => $_GET['status'] ?? null,
            'category_id' => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
            'search' => $_GET['search'] ?? null,
        ];
        $filters = array_filter($filters);

        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;

        $products = $this->productService->getAll($filters);
        $categories = $this->categoryService->getAll(true);

        // Pagination
        $total = count($products);
        $products = array_slice($products, ($page - 1) * $perPage, $perPage);

        return $this->adminPage('Products', view('admin/products/index', [
            'products' => $products,
            'categories' => $categories,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'status' => $_GET['status'] ?? 'all',
            'search' => $_GET['search'] ?? '',
        ]), [
            'new_url' => '/dashboard/products/create',
            'new_label' => 'Add New Product',
            'breadcrumbs' => [
                'Dashboard' => '/dashboard',
                'Products' => ''
            ]
        ]);
    }

    /**
     * GET /dashboard/products/create
     * Show create product form
     */
    public function create()
    {
        $categories = $this->categoryService->getAll(true);

        return $this->adminPage('Add New Product', view('admin/products/form', [
            'product' => null,
            'isEdit' => false,
            'categories' => $categories,
        ]), [
            'breadcrumbs' => [
                'Dashboard' => '/dashboard',
                'Products' => '/dashboard/products',
                'Add New' => ''
            ]
        ]);
    }

    /**
     * POST /dashboard/products/store
     */
    public function store()
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'slug' => $_POST['slug'] ?? null,
            'sku' => $_POST['sku'] ?? null,
            'description' => $_POST['description'] ?? null,
            'short_description' => $_POST['short_description'] ?? null,
            'price' => !empty($_POST['price']) ? (float)$_POST['price'] : null,
            'sale_price' => !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null,
            'stock' => (int)($_POST['stock'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'type' => $_POST['type'] ?? 'simple',
            'status' => $_POST['status'] ?? 'draft',
            'is_featured' => isset($_POST['is_featured']),
        ];

        try {
            $product = $this->productService->create($data);
            return $this->redirect('/dashboard/products');
        } catch (\Throwable $e) {
            $categories = $this->categoryService->getAll(true);
            return $this->adminPage('Add New Product', view('admin/products/form', [
                'product' => (object)$data,
                'isEdit' => false,
                'categories' => $categories,
                'error' => $e->getMessage(),
            ]), [
                'breadcrumbs' => [
                    'Dashboard' => '/dashboard',
                    'Products' => '/dashboard/products',
                    'Add New' => ''
                ]
            ]);
        }
    }

    /**
     * GET /dashboard/products/edit/{id}
     * Show edit product form
     */
    public function edit(string $id)
    {
        $product = $this->productService->getById((int)$id);

        if (!$product) {
            return $this->redirect('/dashboard/products');
        }

        $categories = $this->categoryService->getAll(true);

        return $this->adminPage('Edit Product', view('admin/products/form', [
            'product' => $product,
            'isEdit' => true,
            'categories' => $categories,
        ]), [
            'breadcrumbs' => [
                'Dashboard' => '/dashboard',
                'Products' => '/dashboard/products',
                'Edit' => ''
            ]
        ]);
    }

    /**
     * POST /dashboard/products/update/{id}
     */
    public function update(string $id)
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'slug' => $_POST['slug'] ?? null,
            'sku' => $_POST['sku'] ?? null,
            'description' => $_POST['description'] ?? null,
            'short_description' => $_POST['short_description'] ?? null,
            'price' => !empty($_POST['price']) ? (float)$_POST['price'] : null,
            'sale_price' => !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null,
            'stock' => (int)($_POST['stock'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'type' => $_POST['type'] ?? 'simple',
            'status' => $_POST['status'] ?? 'draft',
            'is_featured' => isset($_POST['is_featured']),
        ];

        try {
            $product = $this->productService->update((int)$id, $data);
            if ($product) {
                return $this->redirect('/dashboard/products');
            }
        } catch (\Throwable $e) {
            // Error handling
        }

        return $this->redirect('/dashboard/products');
    }

    /**
     * POST /dashboard/products/delete/{id}
     */
    public function delete(string $id)
    {
        $this->productService->delete((int)$id);
        return $this->redirect('/dashboard/products');
    }
}

