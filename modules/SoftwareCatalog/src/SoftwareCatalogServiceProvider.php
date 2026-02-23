<?php

declare(strict_types=1);

namespace Modules\SoftwareCatalog;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class SoftwareCatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\SoftwareCatalogController::class, fn($app) => new Controllers\SoftwareCatalogController($app));
        $this->singleton(Controllers\SoftwareCatalogAdminController::class, fn($app) => new Controllers\SoftwareCatalogAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- API ---
        $router->get('/api/catalog',           [Controllers\SoftwareCatalogController::class, 'index']);
        $router->get('/api/catalog/{id}',      [Controllers\SoftwareCatalogController::class, 'show']);
        $router->post('/api/catalog',          [Controllers\SoftwareCatalogController::class, 'store']);
        $router->put('/api/catalog/{id}',      [Controllers\SoftwareCatalogController::class, 'update']);
        $router->delete('/api/catalog/{id}',   [Controllers\SoftwareCatalogController::class, 'destroy']);
        $router->get('/api/catalog/software',  [Controllers\SoftwareCatalogController::class, 'software']);
        $router->get('/api/catalog/plugins',   [Controllers\SoftwareCatalogController::class, 'plugins']);
        $router->get('/api/catalog/themes',    [Controllers\SoftwareCatalogController::class, 'themes']);

        // --- Admin UI ---
        $router->get('/dashboard/catalog',                  [Controllers\SoftwareCatalogAdminController::class, 'index']);
        $router->get('/dashboard/catalog/create',           [Controllers\SoftwareCatalogAdminController::class, 'create']);
        $router->post('/dashboard/catalog/create',          [Controllers\SoftwareCatalogAdminController::class, 'store']);
        $router->get('/dashboard/catalog/{id}/edit',        [Controllers\SoftwareCatalogAdminController::class, 'edit']);
        $router->put('/dashboard/catalog/{id}/edit',        [Controllers\SoftwareCatalogAdminController::class, 'update']);
    }
}
