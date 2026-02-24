<?php

declare(strict_types=1);

namespace Modules\StaticPages;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class StaticPagesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\PageController::class, fn($app) => new Controllers\PageController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- Catch-all for static pages ---
        // Placing it at the end of other routes if possible, or using specific slugs
        $router->get('/about-us',      [Controllers\PageController::class, 'show']);
        $router->get('/contact',       [Controllers\PageController::class, 'show']);
        $router->get('/privacy-policy',[Controllers\PageController::class, 'show']);
        
        $router->get('/api/pages/seed', [Controllers\PageController::class, 'seed']);
    }
}
