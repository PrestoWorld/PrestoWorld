<?php

declare(strict_types=1);

namespace Modules\LicenseManager;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class LicenseManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\LicenseController::class, fn($app) => new Controllers\LicenseController($app));
        $this->singleton(Controllers\LicenseAdminController::class, fn($app) => new Controllers\LicenseAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- API ---
        $router->get('/api/licenses',              [Controllers\LicenseController::class, 'index']);
        $router->get('/api/licenses/{id}',         [Controllers\LicenseController::class, 'show']);
        $router->post('/api/licenses',             [Controllers\LicenseController::class, 'store']);
        $router->put('/api/licenses/{id}',         [Controllers\LicenseController::class, 'update']);
        $router->delete('/api/licenses/{id}',      [Controllers\LicenseController::class, 'destroy']);
        $router->post('/api/licenses/{id}/activate',   [Controllers\LicenseController::class, 'activate']);
        $router->post('/api/licenses/{id}/deactivate', [Controllers\LicenseController::class, 'deactivate']);
        $router->post('/api/licenses/{id}/revoke',     [Controllers\LicenseController::class, 'revoke']);
        $router->get('/api/license/verify',            [Controllers\LicenseController::class, 'verify']);

        // --- Admin UI ---
        $router->get('/dashboard/licenses',                  [Controllers\LicenseAdminController::class, 'index']);
        $router->get('/dashboard/licenses/create',           [Controllers\LicenseAdminController::class, 'create']);
        $router->post('/dashboard/licenses/create',          [Controllers\LicenseAdminController::class, 'store']);
        $router->get('/dashboard/licenses/{id}/edit',        [Controllers\LicenseAdminController::class, 'edit']);
        $router->put('/dashboard/licenses/{id}/edit',        [Controllers\LicenseAdminController::class, 'update']);
    }
}
