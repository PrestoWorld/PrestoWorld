<?php

declare(strict_types=1);

namespace App\Foundation\Admin\Controllers;

use App\Foundation\Admin\AdminController;
use App\Services\CategoryService;

class CategoryController extends AdminController
{
    private CategoryService $categoryService;

    public function __construct(\Witals\Framework\Application $app)
    {
        parent::__construct($app);
        $this->categoryService = $app->make(CategoryService::class);
    }

    /**
     * GET /dashboard/categories
     */
    public function index()
    {
        $categories = $this->categoryService->getAll();
        $tree = $this->categoryService->getTree();

        return $this->adminPage('Categories', view('admin/categories/index', [
            'categories' => $categories,
            'tree' => $tree,
        ]), [
            'new_url' => '/dashboard/categories/create',
            'new_label' => 'Add New Category',
            'breadcrumbs' => [
                'Dashboard' => '/dashboard',
                'Categories' => ''
            ]
        ]);
    }

    /**
     * GET /dashboard/categories/create
     */
    public function create()
    {
        $categories = $this->categoryService->getAll();

        return $this->adminPage('Add New Category', view('admin/categories/form', [
            'category' => null,
            'isEdit' => false,
            'parentCategories' => $categories,
        ]), [
            'breadcrumbs' => [
                'Dashboard' => '/dashboard',
                'Categories' => '/dashboard/categories',
                'Add New' => ''
            ]
        ]);
    }

    /**
     * POST /dashboard/categories/store
     */
    public function store()
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'slug' => $_POST['slug'] ?? null,
            'description' => $_POST['description'] ?? null,
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']),
        ];

        try {
            $category = $this->categoryService->create($data);
            return $this->redirect('/dashboard/categories');
        } catch (\Throwable $e) {
            return $this->adminPage('Add New Category', view('admin/categories/form', [
                'category' => (object)$data,
                'isEdit' => false,
                'parentCategories' => $this->categoryService->getAll(),
                'error' => $e->getMessage(),
            ]), [
                'breadcrumbs' => [
                    'Dashboard' => '/dashboard',
                    'Categories' => '/dashboard/categories',
                    'Add New' => ''
                ]
            ]);
        }
    }

    /**
     * GET /dashboard/categories/edit/{id}
     */
    public function edit(string $id)
    {
        $category = $this->categoryService->getById((int)$id);

        if (!$category) {
            return $this->redirect('/dashboard/categories');
        }

        $categories = $this->categoryService->getAll();

        return $this->adminPage('Edit Category', view('admin/categories/form', [
            'category' => $category,
            'isEdit' => true,
            'parentCategories' => array_filter($categories, fn($c) => $c->getId() !== $category->getId()),
        ]), [
            'breadcrumbs' => [
                'Dashboard' => '/dashboard',
                'Categories' => '/dashboard/categories',
                'Edit' => ''
            ]
        ]);
    }

    /**
     * POST /dashboard/categories/update/{id}
     */
    public function update(string $id)
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'slug' => $_POST['slug'] ?? null,
            'description' => $_POST['description'] ?? null,
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']),
        ];

        try {
            $category = $this->categoryService->update((int)$id, $data);
            if ($category) {
                return $this->redirect('/dashboard/categories');
            }
        } catch (\Throwable $e) {
            // Error handling
        }

        return $this->redirect('/dashboard/categories');
    }

    /**
     * POST /dashboard/categories/delete/{id}
     */
    public function delete(string $id)
    {
        $this->categoryService->delete((int)$id);
        return $this->redirect('/dashboard/categories');
    }
}
