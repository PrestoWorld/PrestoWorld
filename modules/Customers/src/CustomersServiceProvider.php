<?php

declare(strict_types=1);

namespace Modules\Customers;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class CustomersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\CustomerController::class, fn($app) => new Controllers\CustomerController($app));
        $this->singleton(Controllers\CustomerAdminController::class, fn($app) => new Controllers\CustomerAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- API ---
        $router->get('/api/customers',           [Controllers\CustomerController::class, 'index']);
        $router->get('/api/customers/{id}',      [Controllers\CustomerController::class, 'show']);
        $router->post('/api/customers',          [Controllers\CustomerController::class, 'store']);
        $router->put('/api/customers/{id}',      [Controllers\CustomerController::class, 'update']);
        $router->delete('/api/customers/{id}',   [Controllers\CustomerController::class, 'destroy']);

        // --- Admin UI ---
        $router->get('/dashboard/customers',                 [Controllers\CustomerAdminController::class, 'index']);
        $router->get('/dashboard/customers/create',          [Controllers\CustomerAdminController::class, 'create']);
        $router->post('/dashboard/customers/create',         [Controllers\CustomerAdminController::class, 'store']);
        $router->get('/dashboard/customers/{id}/edit',       [Controllers\CustomerAdminController::class, 'edit']);
        $router->put('/dashboard/customers/{id}/edit',       [Controllers\CustomerAdminController::class, 'update']);
    }
}
