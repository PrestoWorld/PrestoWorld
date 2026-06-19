<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use PrestoWorld\Contracts\Plugin\HooksRegistryInterface;
use Witals\Framework\Module\Contracts\HookInterface;
use App\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(HookDispatcher::class, fn($app) => new HookDispatcher());
        $this->app->singleton(HookInterface::class, HookDispatcher::class);

        $this->singleton(HooksRegistry::class, fn($app) => new HooksRegistry());

        $this->singleton(
            HooksRegistryInterface::class,
            fn($app) => $app->make(HooksRegistry::class),
        );

        $this->singleton(
            PluginStoreInterface::class,
            fn($app) => new PluginStore($app->make(\Cycle\Database\DatabaseProviderInterface::class)),
        );

        $this->singleton(HooksValidator::class, fn($app) => new HooksValidator($app->make(HooksRegistry::class)));

        $this->singleton(PluginManager::class, function ($app) {
            $manager = new PluginManager(
                $app,
                $app->make(PluginStoreInterface::class),
                $app->make(HookDispatcher::class),
                $app->make(HooksValidator::class),
                $app->make(\Psr\Log\LoggerInterface::class),
            );

            return $manager;
        });
    }

    public function boot(): void
    {
        $manager = $this->app->make(PluginManager::class);
        $manager->loadEnabledPlugins();
    }
}
