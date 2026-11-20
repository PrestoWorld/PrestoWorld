<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use PrestoWorld\Contracts\Plugin\HooksRegistryInterface;
use PrestoWorld\Core\Plugin\PluginDiscoveryService;
use PrestoWorld\Core\Plugin\SmartPluginScanner;
use Witals\Framework\Module\Contracts\HookInterface;
use App\Support\ServiceProvider;
use Prestoworld\MarketplaceSdk\MarketplaceClient;
use Prestoworld\MarketplaceSdk\PrestoWorldRepository;
use PrestoWorld\Marketplace\WpOrg\WpOrgRepository;

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

        // Marketplace client singleton
        $this->singleton(MarketplaceClient::class, function ($app) {
            $client = new MarketplaceClient(
                $app->config('marketplace.api_url', env('MARKETPLACE_API_URL')),
            );
            $apiKey = $app->config('marketplace.api_key', env('MARKETPLACE_API_KEY'));
            if ($apiKey) {
                $client->setApiKey($apiKey);
            }
            return $client;
        });

        // Register marketplace repositories
        $this->singleton(PrestoWorldRepository::class, function ($app) {
            $repo = new PrestoWorldRepository($app->make(MarketplaceClient::class));
            // Register with plugin manager
            $manager = $app->make(PluginManager::class);
            $manager->addRepository($repo);
            return $repo;
        });

        $this->singleton(WpOrgRepository::class, function ($app) {
            $repo = new WpOrgRepository($app->make(MarketplaceClient::class));
            $manager = $app->make(PluginManager::class);
            $manager->addRepository($repo);
            return $repo;
        });

        // Smart plugin discovery — scans plugin roots and caches in
        // storage/framework/cache/plugins-discovery.php. Used by the
        // admin PluginsController to surface both installed and available
        // plugins without touching the network or database.
        $this->singleton(SmartPluginScanner::class, fn($app) => new SmartPluginScanner());

        $this->singleton(PluginDiscoveryService::class, function ($app) {
            $roots = [
                $app->basePath('plugins'),
                $app->basePath('content/plugins'),
                $app->basePath('storage/wp-content/plugins'),
            ];

            $logger = $app->has(\Psr\Log\LoggerInterface::class)
                ? $app->make(\Psr\Log\LoggerInterface::class)
                : null;

            return new PluginDiscoveryService(
                $app->make(SmartPluginScanner::class),
                $app->basePath('storage/framework/cache'),
                $roots,
                $logger,
            );
        });

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
