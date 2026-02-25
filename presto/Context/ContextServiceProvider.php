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
        // 1. Header Navigation - Products Dropdown
        $products = new \PrestoWorld\Context\Items\MenuItemContext('nav_products', 'Products', '#', priority: 10);
        
        $cloudGroup = new \PrestoWorld\Context\Items\DropdownGroupContext('group_cloud', 'Cloud & Infrastructure', priority: 10);
        $cloudGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_hosting', 'Hosting', '/hosting', '☁️', 'High-speed web hosting', priority: 10));
        $cloudGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_vps', 'VPS Server', '/vps', '⚡', 'Powerful virtual servers', priority: 20));
        
        $assetGroup = new \PrestoWorld\Context\Items\DropdownGroupContext('group_assets', 'Digital Assets', priority: 20);
        $assetGroup->add(new \PrestoWorld\Context\Items\MenuItemContext('item_themes', 'Premium Themes', '/code/themes', '🎨', 'Beautiful web designs', priority: 10));
        
        $products->addGroup($cloudGroup);
        $products->addGroup($assetGroup);

        $contexts->register('header.nav', $products);
        $contexts->register('header.nav', new \PrestoWorld\Context\Items\MenuItemContext('nav_services', 'Services', '#', priority: 20, badge: 'PRO'));
        $contexts->register('header.nav', new \PrestoWorld\Context\Items\MenuItemContext('nav_web_templates', 'Website Templates', '/web-templates', priority: 30));
        $contexts->register('header.nav', new \PrestoWorld\Context\Items\MenuItemContext('nav_blog', 'Blog', '/blog', priority: 40));

        // 2. Header Actions - Avatar (Placeholder)
        $contexts->register('header.actions', new \PrestoWorld\Context\Items\AvatarContext(
            id: 'header_user_area',
            priority: 100
        ));
    }
}
