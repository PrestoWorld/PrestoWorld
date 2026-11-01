<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin;

use App\Support\ServiceProvider;
use PrestoWorld\Core\Admin\Adapters\WpBridgeMenuAdapter;
use PrestoWorld\Core\Admin\Context\AdminContext;
use PrestoWorld\Core\Admin\Contracts\AdminContextInterface;
use PrestoWorld\Core\Admin\Contracts\AdminRegistryInterface;
use PrestoWorld\Core\Admin\Contracts\AdminRendererInterface;
use PrestoWorld\Core\Admin\Registry\AdminPageRegistry;
use PrestoWorld\Core\Admin\Renderers\SpaDashboardRenderer;
use PrestoWorld\Core\Admin\Renderers\WpAdminRenderer;

class AdminDashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdminContextInterface::class, AdminContext::class);
        $this->app->singleton(AdminContext::class, fn($app) => $app->make(AdminContextInterface::class));

        $this->app->singleton(AdminRegistryInterface::class, AdminPageRegistry::class);
        $this->app->singleton(AdminPageRegistry::class, fn($app) => $app->make(AdminRegistryInterface::class));

        $this->app->singleton(WpBridgeMenuAdapter::class);

        $this->app->singleton(WpAdminRenderer::class, fn($app) => new WpAdminRenderer($app->make(WpBridgeMenuAdapter::class)));
        $this->app->singleton(SpaDashboardRenderer::class, SpaDashboardRenderer::class);

        $this->app->singleton(AdminDashboardManager::class, function ($app) {
            $manager = new AdminDashboardManager(
                $app->make(AdminContextInterface::class),
                $app->make(AdminRegistryInterface::class),
                [
                    $app->make(WpAdminRenderer::class),
                    $app->make(SpaDashboardRenderer::class),
                ]
            );
            return $manager;
        });
    }

    public function boot(): void
    {
        // Intentionally left minimal; pages registered by modules/providers
    }
}
