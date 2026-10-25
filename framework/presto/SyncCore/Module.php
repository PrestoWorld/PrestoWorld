<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore;

use App\Http\Routing\Contracts\RouteRegistryInterface;
use PrestoWorld\Core\SyncCore\Domain\Contracts\ConnectorRegistryInterface;
use PrestoWorld\Core\SyncCore\Engine\ConnectorRegistry;
use PrestoWorld\Core\SyncCore\Engine\SyncEngine;
use PrestoWorld\Core\SyncCore\Http\SyncAdminController;
use PrestoWorld\Core\SyncCore\Storage\SyncJobRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncMappingRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncRecordRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncSettingsRepository;
use Witals\Framework\Application;
use Witals\Framework\Console\Kernel;
use Witals\Framework\Module\Module as WitalsModule;

class Module extends WitalsModule
{
    public function __construct(
        protected Application $app,
        protected string $path = '',
        protected array $metadata = [],
    ) {
        if ($path === '') {
            $path = __DIR__;
        }
        if ($metadata === []) {
            $metadata = ['name' => 'sync-core'];
        }

        parent::__construct($app, $path, $metadata);
    }

    public function register(): void
    {
        $app = $this->app;
        $prefix = fn (Application $app): string => (string) ($app->config('ecommerce.table_prefix', 'pw_') ?: 'pw_');

        $app->singleton(SyncSettingsRepository::class, fn (Application $app) => new SyncSettingsRepository(
            $app->make(\Cycle\Database\DatabaseInterface::class),
            $prefix($app),
        ));
        $app->singleton(SyncJobRepository::class, fn (Application $app) => new SyncJobRepository(
            $app->make(\Cycle\Database\DatabaseInterface::class),
            $prefix($app),
        ));
        $app->singleton(SyncRecordRepository::class, fn (Application $app) => new SyncRecordRepository(
            $app->make(\Cycle\Database\DatabaseInterface::class),
            $prefix($app),
        ));
        $app->singleton(SyncMappingRepository::class, fn (Application $app) => new SyncMappingRepository(
            $app->make(\Cycle\Database\DatabaseInterface::class),
            $prefix($app),
        ));

        $app->singleton(ConnectorRegistryInterface::class, fn () => new ConnectorRegistry());
        $app->singleton(ConnectorRegistry::class, fn (Application $app) => $app->make(ConnectorRegistryInterface::class));

        $app->singleton(SyncEngine::class, fn (Application $app) => new SyncEngine(
            $app,
            $app->make(ConnectorRegistryInterface::class),
            $app->make(SyncSettingsRepository::class),
            $app->make(SyncJobRepository::class),
            $app->make(SyncRecordRepository::class),
            $app->make(SyncMappingRepository::class),
        ));
    }

    public function boot(): void
    {
        $this->registerAdminRoutes();
        $this->registerConsoleCommands();
    }

    public function getName(): string
    {
        return 'SyncCore';
    }

    private function registerAdminRoutes(): void
    {
        if (!$this->app->has(RouteRegistryInterface::class)) {
            return;
        }

        $cachePath = $this->app->storagePath('framework/cache/routes.php');
        if (is_file($cachePath)) {
            @unlink($cachePath);
        }

        $registry = $this->app->make(RouteRegistryInterface::class);
        if (!$registry instanceof RouteRegistryInterface) {
            return;
        }

        $action = [SyncAdminController::class, 'handle'];
        $base = '/api/admin/sync';
        $routes = [
            ['GET', $base . '/connectors'],
            ['GET', $base . '/{platform}'],
            ['GET', $base . '/{platform}/overview'],
            ['GET', $base . '/{platform}/settings'],
            ['PUT', $base . '/{platform}/settings'],
            ['POST', $base . '/{platform}/test'],
            ['GET', $base . '/{platform}/resources'],
            ['GET', $base . '/{platform}/jobs'],
            ['POST', $base . '/{platform}/jobs'],
            ['GET', $base . '/{platform}/jobs/{job}'],
            ['POST', $base . '/{platform}/jobs/{job}/cancel'],
            ['GET', $base . '/{platform}/jobs/{job}/records'],
            ['GET', $base . '/{platform}/mappings'],
            ['DELETE', $base . '/{platform}/mappings/{mapping}'],
        ];

        foreach ($routes as [$method, $path]) {
            $registry->addRoute($method, $path, $action, RouteRegistryInterface::PRIORITY_MODULE);
        }
    }

    private function registerConsoleCommands(): void
    {
        if (!$this->app->has(Kernel::class)) {
            return;
        }

        $kernel = $this->app->make(Kernel::class);
        if (!$kernel instanceof Kernel) {
            return;
        }

        $kernel->register(\PrestoWorld\Core\SyncCore\Console\SyncRunJobCommand::class);
    }
}
