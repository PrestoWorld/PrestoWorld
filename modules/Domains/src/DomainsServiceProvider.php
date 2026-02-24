<?php

declare(strict_types=1);

namespace Modules\Domains;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class DomainsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\DomainsAdminController::class, fn($app) => new Controllers\DomainsAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // Admin Routes
        $router->get('/dashboard/domains',           [Controllers\DomainsAdminController::class, 'index']);
        $router->get('/dashboard/domains/create',    [Controllers\DomainsAdminController::class, 'create']);
    }
}
