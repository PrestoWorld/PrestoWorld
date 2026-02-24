<?php

declare(strict_types=1);

namespace Modules\Orders;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\OrderController::class, fn($app) => new Controllers\OrderController($app));
        $this->singleton(Controllers\OrderAdminController::class, fn($app) => new Controllers\OrderAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- API ---
        $router->get('/api/orders',          [Controllers\OrderController::class, 'index']);
        $router->get('/api/orders/{id}',     [Controllers\OrderController::class, 'show']);
        $router->post('/api/orders',         [Controllers\OrderController::class, 'store']);
        $router->put('/api/orders/{id}',     [Controllers\OrderController::class, 'update']);
        $router->delete('/api/orders/{id}',  [Controllers\OrderController::class, 'destroy']);

        // --- Admin UI ---
        $router->get('/dashboard/orders',                 [Controllers\OrderAdminController::class, 'index']);
        $router->get('/dashboard/orders/create',          [Controllers\OrderAdminController::class, 'create']);
        $router->post('/dashboard/orders/create',         [Controllers\OrderAdminController::class, 'store']);
        $router->get('/dashboard/orders/{id}',            [Controllers\OrderAdminController::class, 'show']);
        $router->get('/dashboard/orders/{id}/edit',       [Controllers\OrderAdminController::class, 'edit']);
        $router->put('/dashboard/orders/{id}/edit',       [Controllers\OrderAdminController::class, 'update']);
    }
}
