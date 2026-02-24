<?php

declare(strict_types=1);

namespace Modules\Infrastructure;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\InfrastructureAdminController::class, fn($app) => new Controllers\InfrastructureAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // Admin Routes
        $router->get('/dashboard/infrastructure/ssl',   [Controllers\InfrastructureAdminController::class, 'ssl']);
        $router->get('/dashboard/infrastructure/email', [Controllers\InfrastructureAdminController::class, 'email']);
    }
}
