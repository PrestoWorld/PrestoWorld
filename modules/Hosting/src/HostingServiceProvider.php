<?php

declare(strict_types=1);

namespace Modules\Hosting;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class HostingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\HostingAdminController::class, fn($app) => new Controllers\HostingAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // Admin Routes
        $router->get('/dashboard/hosting',           [Controllers\HostingAdminController::class, 'index']);
        $router->get('/dashboard/hosting/plans',     [Controllers\HostingAdminController::class, 'plans']);
        $router->get('/dashboard/hosting/create',    [Controllers\HostingAdminController::class, 'create']);
    }
}
