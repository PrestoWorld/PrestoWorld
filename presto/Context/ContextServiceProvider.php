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
        $contexts->register('dashboard.menu', new \PrestoWorld\Context\Items\MenuItemContext('menu_users', 'Users', '/dashboard/users', '👥', priority: 60));

        // 2. Content Management (Blog, Static Pages)
        $contentGroup = new \PrestoWorld\Context\Items\DropdownGroupContext('group_content', 'Content', '📝', priority: 15);
        $contentGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_blog', 'Blog Posts', '/dashboard/blog', '✒️', priority: 10));
        $contentGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_categories', 'Categories', '/dashboard/blog/categories', '📁', priority: 20));
        $contentGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_pages', 'Static Pages', '/dashboard/pages', '📄', priority: 30));
        $contexts->register('dashboard.menu', $contentGroup);

        // 3. Marketplace & Extensions (Theme/Plugin Store)
        $appearanceGroup = new \PrestoWorld\Context\Items\DropdownGroupContext('group_appearance', 'Appearance', '🎨', priority: 20);
        $appearanceGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_themes', 'Themes', '/dashboard/themes', '🖼️', priority: 10));
        $appearanceGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_customize', 'Customize', '/dashboard/customize', '✨', priority: 20));
        $contexts->register('dashboard.menu', $appearanceGroup);

        $pluginGroup = new \PrestoWorld\Context\Items\DropdownGroupContext('group_plugins', 'Plugins', '🔌', priority: 30);
        $pluginGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_plugins_installed', 'Installed Plugins', '/dashboard/plugins', '🛠️', priority: 10));
        $pluginGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_plugins_add', 'Add New', '/dashboard/plugins/install', '➕', priority: 20));
        $contexts->register('dashboard.menu', $pluginGroup);

        // 4. Personal Account
        $accountGroup = new \PrestoWorld\Context\Items\DropdownGroupContext('group_account', 'Account', '👤', priority: 70);
        $accountGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_profile', 'My Profile', '/dashboard/profile', '🆔', priority: 10));
        $accountGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_security', 'Security', '/dashboard/profile/security', '🔒', priority: 20));
        $contexts->register('dashboard.menu', $accountGroup);

        // 3. Header Navigation (Generic Framework)
        $contexts->register('header.nav', new \PrestoWorld\Context\Items\MenuItemContext('nav_home', 'Visit Site', '/', priority: 10));
        $contexts->register('header.nav', new \PrestoWorld\Context\Items\MenuItemContext('nav_docs', 'Documentation', 'https://prestoworld.com/docs', priority: 20));

        // 3. Header Actions - Avatar (Placeholder)
        $contexts->register('header.actions', new \PrestoWorld\Context\Items\AvatarContext(
            id: 'header_user_area',
            priority: 100
        ));
    }
}
