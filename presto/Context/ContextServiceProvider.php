<?php

declare(strict_types=1);

namespace PrestoWorld\Context;

use Witals\Framework\Support\ServiceProvider;
use PrestoWorld\Context\Contracts\ContextRegistryInterface;
use PrestoWorld\Context\Contexts\HeaderNavContext;
use PrestoWorld\Context\Contexts\HeaderActionsContext;
use PrestoWorld\Context\Contexts\FooterContext;
use PrestoWorld\Context\Contexts\DashboardMenuContext;
use PrestoWorld\Context\Contexts\DashboardWidgetsContext;
use PrestoWorld\Context\Contexts\PortalMenuContext;
use PrestoWorld\Context\Contexts\HomeSectionContext;

class ContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ContextRegistryInterface::class, ContextRegistry::class);
        $this->app->singleton(ContextManager::class, function ($app) {
            return new ContextManager($app->make(ContextRegistryInterface::class));
        });

        // Setup aliases
        $this->app->alias(ContextManager::class, 'contexts');
    }

    public function boot(): void
    {
        $registry = $this->app->make(ContextRegistryInterface::class);

        // Define core contexts
        $registry->define(new HeaderNavContext());
        $registry->define(new HeaderActionsContext());
        $registry->define(new FooterContext());
        $registry->define(new DashboardMenuContext());
        $registry->define(new DashboardWidgetsContext());
        $registry->define(new PortalMenuContext());
        $registry->define(new HomeSectionContext());

        // Register default items (Migration fallback)
        $this->registerDefaultItems($this->app->make(ContextManager::class));
    }

    protected function registerDefaultItems(ContextManager $contexts): void
    {
        // 1. Dashboard Sidebar Menu (Generic CMS)
        $contexts->register('dashboard.menu', new \PrestoWorld\Context\Items\MenuItemContext('menu_dashboard', 'Dashboard', '/dashboard', '📊', priority: 10));
        
        $systemGroup = new \PrestoWorld\Context\Items\DropdownGroupContext('group_system', 'System Settings', priority: 100);
        $systemGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_general', 'General', '/dashboard/settings', '🔧', priority: 10));
        $systemGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_modules', 'Modules', '/dashboard/modules', '📦', priority: 20));
        
        $contexts->register('dashboard.menu', $systemGroup);
        $contexts->register('dashboard.menu', new \PrestoWorld\Context\Items\MenuItemContext('menu_users', 'User Management', '/dashboard/users', '👥', priority: 30));

        // 2. Header Navigation (Generic Framework)
        $contexts->register('header.nav', new \PrestoWorld\Context\Items\MenuItemContext('nav_home', 'Visit Site', '/', priority: 10));
        $contexts->register('header.nav', new \PrestoWorld\Context\Items\MenuItemContext('nav_docs', 'Documentation', 'https://prestoworld.com/docs', priority: 20));

        // 3. Header Actions - Avatar (Placeholder)
        $contexts->register('header.actions', new \PrestoWorld\Context\Items\AvatarContext(
            id: 'header_user_area',
            priority: 100
        ));
    }
}
