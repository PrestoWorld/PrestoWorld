<?php

declare(strict_types=1);

namespace Modules\Dashboard;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\DashboardController::class, function ($app) {
            return new Controllers\DashboardController($app);
        });
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        
        // Register admin dashboard route
        $router->get('/dashboard', [Controllers\DashboardController::class, 'index']);
    }
}
