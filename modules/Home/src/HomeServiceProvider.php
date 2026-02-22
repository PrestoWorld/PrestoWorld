<?php

declare(strict_types=1);

namespace Modules\Home;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

/**
 * Home Module Service Provider
 * 
 * Sets up the modular home page infrastructure
 */
class HomeServiceProvider extends ServiceProvider
{
    /**
     * Register module services
     */
    public function register(): void
    {
        // Bind the controller as a singleton for performance
        $this->singleton(Controllers\HomeController::class, function ($app) {
            return new Controllers\HomeController($app);
        });
    }

    /**
     * Boot the module
     */
    public function boot(): void
    {
        error_log("HomeModule: Booting...");

        // Register the module's controller for the root path
        $router = $this->app->make(Router::class);
        $router->get('/', [Controllers\HomeController::class, 'index']);

        // Register default global hooks
        $this->registerGlobalHooks();
    }

    /**
     * Register hooks that allow other parts of the system to customize the home page
     */
    protected function registerGlobalHooks(): void
    {
        // Example: If someone wants to completely replace the home route logic
        // they can use the existing 'handleHome' hook in Kernel, 
        // but we're providing a new, more granular section-based approach.
        
        $hooks = $this->app->make('hooks');

        // We can hook into the main home route if we want to override it automatically
        // But it's safer to let the user decide. 
        // However, the request was "without changing module code", 
        // so I'll show how to use this module's controller for the main home page.
    }
}
