<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ModuleManager;

use Witals\Framework\Module\Module as WitalsModule;
use Witals\Framework\Module\ModuleManager as WitalsModuleManager;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository as MenuContract;
use PrestoWorld\Modules\Admin\Screen\Screen;

class Module extends WitalsModule
{
    public function getName(): string
    {
        return 'Module Manager';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDependencies(): array
    {
        return [
            'admin' => '1.0.0',
        ];
    }

    public function register(): void
    {
        $this->app->alias(WitalsModuleManager::class, 'module.manager');

        $this->registerAdminMenu();
        $this->registerModuleScreen();
    }

    public function boot(): void
    {
    }

    protected function registerAdminMenu(): void
    {
        $menu = $this->app->make(MenuContract::class);

        $menu->registerGroup('modules-group', 'Modules', icon: 'Layers', priority: 70);

        $menu->registerItem(
            'modules-group',
            'Manage Modules',
            '/admin/modules',
            icon: 'Layers',
            priority: 10,
            id: 'modules',
            screenId: 'modules-manager'
        );
    }

    protected function registerModuleScreen(): void
    {
        $registry = $this->app->make(
            \PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenRegistryInterface::class
        );

        $registry->registerScreen(new Screen(
            id: 'modules-manager',
            title: 'Module Manager',
            icon: 'Layers',
            position: 60,
            component: 'module-manager-screen',
            source: 'module-manager'
        ));
    }
}
