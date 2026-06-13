<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin;

use Witals\Framework\Module\Module as WitalsModule;
use Witals\Framework\Contracts\View\Factory as ViewFactory;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository as MenuContract;
use PrestoWorld\Contracts\Admin\SkinInterface;

class Module extends WitalsModule
{
    protected bool $skinsRegistered = false;

    public function getName(): string
    {
        return 'PrestoWorld Admin Engine';
    }

    public function register(): void
    {
        $this->app->singleton(\PrestoWorld\Modules\Admin\SkinManager::class, function ($app) {
            return new \PrestoWorld\Modules\Admin\SkinManager();
        });

        $this->app->singleton(MenuContract::class, function () {
            return new \PrestoWorld\Modules\Admin\Menu\MenuContextRepository();
        });

        $this->app->alias(MenuContract::class, 'admin.menu');
        $this->app->alias(\PrestoWorld\Modules\Admin\SkinManager::class, 'admin.skins');
    }

    public function boot(): void
    {
        $this->registerSkins();
    }

    protected function registerSkins(): void
    {
        if ($this->skinsRegistered) {
            return;
        }

        $skinManager = $this->app->make(\PrestoWorld\Modules\Admin\SkinManager::class);

        $this->registerPrestoModernSkin($skinManager);
        $this->registerPrestoSpaSkin($skinManager);

        $this->skinsRegistered = true;
    }

    protected function registerPrestoModernSkin(SkinManager $manager): void
    {
        $view = $this->app->make(ViewFactory::class);

        $skin = new \PrestoWorld\Modules\Admin\Skins\PrestoModern\PrestoModernSkin($view);
        $manifest = $skin::getManifest();

        $manager->registerSkin($skin, $manifest);
    }

    protected function registerPrestoSpaSkin(\PrestoWorld\Modules\Admin\SkinManager $manager): void
    {
        $assets = $this->app->make(\Witals\Framework\Support\Assets\Contracts\AssetRegistryInterface::class);

        $skin = new \PrestoWorld\Modules\Admin\Skins\PrestoSpa\PrestoSpaSkin($assets);
        $manifest = $skin::getManifest();

        $manager->registerSkin($skin, $manifest);
    }
}
