<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin;

use Witals\Framework\Module\Module as WitalsModule;
use Witals\Framework\Contracts\View\Factory as ViewFactory;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository as MenuContract;
use PrestoWorld\Modules\Admin\Dashboard\DashboardWidget;

class Module extends WitalsModule
{
    protected bool $skinsRegistered = false;

    public function getName(): string
    {
        return 'PrestoWorld Admin Engine';
    }

    public function register(): void
    {
        $this->registerSkinManager();
        $this->registerMenuRepository();
        $this->registerDashboard();
    }

    public function boot(): void
    {
        $this->registerSkins();
        $this->setActiveSkinFromConfig();
    }

    // ── Skin ─────────────────────────────────────────────────────

    protected function registerSkinManager(): void
    {
        $this->app->singleton(\PrestoWorld\Modules\Admin\SkinManager::class, fn() => new \PrestoWorld\Modules\Admin\SkinManager());
        $this->app->alias(\PrestoWorld\Modules\Admin\SkinManager::class, 'admin.skins');
    }

    protected function registerSkins(): void
    {
        if ($this->skinsRegistered) return;

        $sm = $this->app->make(\PrestoWorld\Modules\Admin\SkinManager::class);
        $this->registerPrestoModernSkin($sm);
        $this->registerPrestoSpaSkin($sm);

        $this->skinsRegistered = true;
    }

    protected function registerPrestoModernSkin(\PrestoWorld\Modules\Admin\SkinManager $manager): void
    {
        $view = $this->app->make(ViewFactory::class);
        $skin = new \PrestoWorld\Modules\Admin\Skins\PrestoModern\PrestoModernSkin($view);
        $manager->registerSkin($skin, $skin::getManifest());
    }

    protected function registerPrestoSpaSkin(\PrestoWorld\Modules\Admin\SkinManager $manager): void
    {
        $assets = $this->app->make(\Witals\Framework\Support\Assets\Contracts\AssetRegistryInterface::class);
        $skin = new \PrestoWorld\Modules\Admin\Skins\PrestoSpa\PrestoSpaSkin($assets);
        $manager->registerSkin($skin, $skin::getManifest());
    }

    protected function setActiveSkinFromConfig(): void
    {
        $skinName = $this->app->config('admin.skin', 'presto-spa');
        $skinManager = $this->app->make(\PrestoWorld\Modules\Admin\SkinManager::class);

        if ($skinManager->hasSkin($skinName)) {
            $skinManager->setActiveSkin($skinName);
        }
    }

    // ── Menu ─────────────────────────────────────────────────────

    protected function registerMenuRepository(): void
    {
        $this->app->singleton(MenuContract::class, fn() => new \PrestoWorld\Modules\Admin\Menu\MenuContextRepository());
        $this->app->alias(MenuContract::class, 'admin.menu');
    }

    // ── Dashboard & Widgets ──────────────────────────────────────

    protected function registerDashboard(): void
    {
        $this->app->singleton(
            \PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetRepository::class,
            fn() => new \PrestoWorld\Modules\Admin\Dashboard\DashboardWidgetRepository(),
        );

        $this->registerDashboardWidgets();
    }

    protected function registerDashboardWidgets(): void
    {
        $repo = $this->app->make(\PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetRepository::class);
        $column = fn(int $idx) => $idx <= 2 ? 1 : 2;

        $widgets = [
            ['at-a-glance', 'At a Glance', '<div class="pw-dashboard-stats"><div class="pw-stat"><span class="pw-stat__value">—</span><span class="pw-stat__label">Posts</span></div><div class="pw-stat"><span class="pw-stat__value">—</span><span class="pw-stat__label">Users</span></div></div>'],
            ['quick-draft', 'Quick Draft', '<form class="pw-quick-draft"><textarea class="pw-quick-draft__input" placeholder="What\'s on your mind?" rows="3"></textarea><button class="pw-quick-draft__submit" type="submit">Save Draft</button></form>'],
            ['activity', 'Activity', '<div class="pw-activity"><p class="pw-activity__empty">No recent activity.</p></div>'],
            ['events-news', 'Events & News', '<div class="pw-news"><p class="pw-news__empty">No recent news.</p></div>'],
        ];

        foreach ($widgets as $i => [$id, $title, $content]) {
            $repo->registerWidget(new DashboardWidget(
                id: $id,
                title: $title,
                content: $content,
                priority: $i + 1,
                column: $column($i + 1),
            ));
        }
    }
}
