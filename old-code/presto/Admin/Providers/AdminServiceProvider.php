<?php

declare(strict_types=1);

namespace PrestoWorld\Admin\Providers;

use App\Support\ServiceProvider;
use PrestoWorld\Admin\AdminManager;
use PrestoWorld\Admin\Drivers\PrestoDashboardDriver;
use App\Http\Routing\Router;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 1. Register the AdminManager singleton
        $this->singleton(AdminManager::class, function ($app) {
            return new AdminManager($app, $app->make(\Psr\Log\LoggerInterface::class));
        });

        // 2. Register Repositories
        $this->singleton(\PrestoWorld\Admin\MenuRepository::class, function ($app) {
            return new \PrestoWorld\Admin\MenuRepository();
        });

        $this->singleton(\PrestoWorld\Admin\DashboardWidgetRepository::class, function ($app) {
            return new \PrestoWorld\Admin\DashboardWidgetRepository();
        });

        // 3. Register Built-in Drivers
        $this->app->make(AdminManager::class)->registerDriver('presto', PrestoDashboardDriver::class);
    }

    public function boot(): void
    {
        /** @var AdminManager $manager */
        $manager = $this->app->make(AdminManager::class);
        
        // Resolve Active Driver
        $driver = $manager->resolveActiveDriver();

        /** @var Router $router */
        $router = $this->app->make(Router::class);

        // Register Routes for the Active Driver
        $driver->registerRoutes($router);

        // Make active driver available globally if needed (Immutability Pattern)
        $this->app->instance('admin.driver', $driver);
    }
}
