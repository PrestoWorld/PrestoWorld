<?php

declare(strict_types=1);

namespace Modules\CustomerPortal;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class CustomerPortalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\PortalController::class, fn($app) => new Controllers\PortalController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- Customer Portal Routes ---
        $router->get('/portal',                 [Controllers\PortalController::class, 'index']);
        $router->get('/portal/services',        [Controllers\PortalController::class, 'services']);
        $router->get('/portal/billing',         [Controllers\PortalController::class, 'billing']);
        $router->get('/portal/profile',         [Controllers\PortalController::class, 'profile']);
        $router->put('/portal/profile',         [Controllers\PortalController::class, 'updateProfile']);
    }
}
