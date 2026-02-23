<?php

declare(strict_types=1);

namespace Modules\Invoices;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class InvoicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\InvoiceController::class, fn($app) => new Controllers\InvoiceController($app));
        $this->singleton(Controllers\InvoiceAdminController::class, fn($app) => new Controllers\InvoiceAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- API ---
        $router->get('/api/invoices',          [Controllers\InvoiceController::class, 'index']);
        $router->get('/api/invoices/{id}',     [Controllers\InvoiceController::class, 'show']);
        $router->post('/api/invoices',         [Controllers\InvoiceController::class, 'store']);
        $router->put('/api/invoices/{id}',     [Controllers\InvoiceController::class, 'update']);
        $router->delete('/api/invoices/{id}',  [Controllers\InvoiceController::class, 'destroy']);

        // --- Admin UI ---
        $router->get('/dashboard/invoices',                 [Controllers\InvoiceAdminController::class, 'index']);
        $router->get('/dashboard/invoices/create',          [Controllers\InvoiceAdminController::class, 'create']);
        $router->post('/dashboard/invoices/create',         [Controllers\InvoiceAdminController::class, 'store']);
        $router->get('/dashboard/invoices/{id}/edit',       [Controllers\InvoiceAdminController::class, 'edit']);
        $router->put('/dashboard/invoices/{id}/edit',       [Controllers\InvoiceAdminController::class, 'update']);
    }
}
