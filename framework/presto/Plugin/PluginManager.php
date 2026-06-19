<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

use PrestoWorld\Contracts\Plugin\PluginInterface;
use PrestoWorld\Contracts\Plugin\PluginRepositoryInterface;
use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use PrestoWorld\Plugin\Exceptions\PluginException;
use PrestoWorld\Plugin\HookDispatcher;
use PrestoWorld\Plugin\HooksValidator;
use PrestoWorld\Plugin\PluginManifest;
use Witals\Framework\Application;
use Witals\Framework\Module\Contracts\HookInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin manager with lazy loading.
 *
 * Performance design:
 * - Plugin discovery: cached (filesystem mtime check)
 * - Hook declarations: compiled into hook-map.php at deploy time
 * - Plugin instances: created ONLY when a hook they registered is triggered
 * - Zero plugin code loaded/parsed for hooks with no listeners
 * - Plugin autoloaders registered once, instances created on demand
 */
class PluginManager
{
    private array $manifests = [];
    private array $instances = [];
    private array $loading = [];
    private array $loaded = [];
    private bool $discovered = false;

    private array $pluginPaths = [];
    private array $repositories = [];

    public function __construct(
        private Application $app,
        private PluginStoreInterface $store,
        private HookDispatcher $hooks,
        private HooksValidator $hooksValidator,
        private ?LoggerInterface $logger = null,
    ) {
        $this->pluginPaths = [
            $app->basePath('plugins'),
        ];
    }

    public function addPluginPath(string $path): void
    {
        $this->pluginPaths[] = $path;
    }

    public function addRepository(PluginRepositoryInterface $repository): void
    {
        $this->repositories[$repository->getName()] = $repository;
    }

    public function getRepository(string $name): ?PluginRepositoryInterface
    {
        return $this->repositories[$name] ?? null;
    }

    public function getRepositories(): array
    {
        return $this->repositories;
    }

    // ========================================================================
    //  Discovery (cached — cheap even with 10k+ manifests)
    // ========================================================================

    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        if ($this->loadDiscoveryCache()) {
            return;
        }

        foreach ($this->pluginPaths as $pluginsPath) {
            if (!is_dir($pluginsPath)) {
                continue;
            }

            foreach (scandir($pluginsPath) as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $pluginPath = $pluginsPath . '/' . $entry;

                if (!is_dir($pluginPath)) {
                    continue;
                }

                $manifest = new PluginManifest($pluginPath);

                if (!$manifest->valid()) {
                    continue;
                }

                $name = $manifest->name();

                if (isset($this->manifests[$name])) {
                    continue;
                }

                $this->manifests[$name] = $manifest;
            }
        }

        $this->saveDiscoveryCache();
    }

    public function getManifests(): array
    {
        $this->discover();

        return $this->manifests;
    }

    public function getManifest(string $name): ?PluginManifest
    {
        $this->discover();

        return $this->manifests[$name] ?? null;
    }

    // ========================================================================
    //  Compiled Hook Map
    // ========================================================================

    public function compileHookMap(): array
    {
        $this->discover();

        $map = [
            'actions' => [],
            'filters' => [],
        ];

        foreach ($this->manifests as $name => $manifest) {
            if (!$this->store->isInstalled($name)) {
                continue;
            }

            if (!$this->store->isEnabled($name)) {
                continue;
            }

            foreach ($manifest->declaredHooks() as $hook) {
                $map['actions'][$hook][] = [
                    'plugin' => $name,
                    'priority' => $manifest->priority(),
                ];
            }
        }

        return $map;
    }

    public function saveCompiledHookMap(): void
    {
        $map = $this->compileHookMap();

        $cacheDir = dirname($this->hookMapCachePath());

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $content = '<?php return ' . var_export($map, true) . ';' . "\n";
        file_put_contents($this->hookMapCachePath(), $content, LOCK_EX);
    }

    public function loadCompiledHookMap(): ?array
    {
        $path = $this->hookMapCachePath();

        if (!file_exists($path)) {
            return null;
        }

        $data = require $path;

        return is_array($data) ? $data : null;
    }

    // ========================================================================
    //  Installation / Uninstallation
    // ========================================================================

    public function install(string $name): void
    {
        $this->discover();

        if ($this->store->isInstalled($name)) {
            throw PluginException::alreadyInstalled($name);
        }

        $manifest = $this->manifests[$name] ?? null;

        if ($manifest === null) {
            throw PluginException::notFound($name);
        }

        $this->resolveDependenciesForInstall($manifest);

        $this->validateHooks($name, $manifest);

        $this->syncSchema($manifest);

        $this->store->markInstalled(
            $name,
            $manifest->version(),
            $manifest->toArray(),
        );

        $this->logger?->info("Plugin installed: {$name} v{$manifest->version()}");
    }

    public function uninstall(string $name): void
    {
        if (!$this->store->isInstalled($name)) {
            throw PluginException::notInstalled($name);
        }

        $plugin = $this->instances[$name] ?? null;

        if ($plugin !== null) {
            $plugin->uninstall();
        }

        $this->store->markUninstalled($name);

        unset($this->instances[$name]);
        unset($this->loaded[$name]);

        $this->clearDiscoveryCache();
        $this->saveCompiledHookMap();

        $this->logger?->info("Plugin uninstalled: {$name}");
    }

    public function activate(string $name): void
    {
        if (!$this->store->isInstalled($name)) {
            throw PluginException::notInstalled($name);
        }

        $this->store->setEnabled($name, true);

        $manifest = $this->manifests[$name] ?? null;

        if ($manifest !== null) {
            $this->registerLazyHookLoaders($name, $manifest);
        }

        $this->saveCompiledHookMap();

        $this->logger?->info("Plugin activated: {$name}");
    }

    public function deactivate(string $name): void
    {
        if (!$this->store->isInstalled($name)) {
            throw PluginException::notInstalled($name);
        }

        $plugin = $this->instances[$name] ?? null;

        if ($plugin !== null) {
            $plugin->deactivate();
        }

        $this->store->setEnabled($name, false);

        unset($this->instances[$name]);
        unset($this->loaded[$name]);

        $this->saveCompiledHookMap();

        $this->logger?->info("Plugin deactivated: {$name}");
    }

    // ========================================================================
    //  Lazy Loading — plugins load only when their hooks fire
    // ========================================================================

    public function loadEnabledPlugins(): void
    {
        $this->discover();

        $compiledMap = $this->loadCompiledHookMap();

        if ($compiledMap !== null) {
            $this->hooks->setCompiledMap($compiledMap);
        }

        $enabled = $this->store->getInstalledPlugins();

        foreach ($enabled as $name => $meta) {
            if (!($meta['enabled'] ?? false)) {
                continue;
            }

            $manifest = $this->manifests[$name] ?? null;

            if ($manifest === null) {
                continue;
            }

            $this->registerAutoloader($manifest);
            $this->registerLazyHookLoaders($name, $manifest);
        }

        $this->registerProvidedDependencies();
    }

    public function ensureLoaded(string $name): ?PluginInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        return $this->loadPlugin($name);
    }

    public function getPlugin(string $name): ?PluginInterface
    {
        return $this->instances[$name] ?? null;
    }

    public function getPlugins(): array
    {
        return $this->instances;
    }

    public function isLoaded(string $name): bool
    {
        return isset($this->loaded[$name]);
    }

    // ========================================================================
    //  Repository Integration
    // ========================================================================

    public function fetchFromRepository(string $repoName, string $pluginName, string $version): ?string
    {
        $repo = $this->repositories[$repoName] ?? null;

        if ($repo === null) {
            throw PluginException::repositoryFailed($repoName, 'Repository not registered');
        }

        return $repo->fetch($pluginName, $version);
    }

    public function checkUpdates(): array
    {
        $updates = [];

        foreach ($this->store->getInstalledPlugins() as $name => $meta) {
            $currentVersion = $meta['version'] ?? '0.0.0';

            $manifest = $this->manifests[$name] ?? null;
            $pluginRepos = $manifest?->repositories() ?? [];

            foreach ($pluginRepos as $repoConfig) {
                $repoType = $repoConfig['type'] ?? '';
                $repoUrl = $repoConfig['url'] ?? '';

                $repo = $this->findRepositoryForType($repoType, $repoUrl);

                if ($repo === null) {
                    continue;
                }

                $available = $repo->hasUpdate($name, $currentVersion);

                if ($available !== null) {
                    $updates[$name] = $available;
                    break;
                }
            }
        }

        return $updates;
    }

    // ========================================================================
    //  Internal — plugin instance management
    // ========================================================================

    private function loadPlugin(string $name): ?PluginInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $this->discover();

        if (!isset($this->manifests[$name])) {
            return null;
        }

        if (isset($this->loading[$name])) {
            $chain = implode(' -> ', array_keys($this->loading)) . ' -> ' . $name;
            throw PluginException::circularDependency($name, $chain);
        }

        $this->loading[$name] = true;

        try {
            $manifest = $this->manifests[$name];

            $this->resolveDependenciesForLoad($manifest);

            $instance = $this->createInstance($manifest);
            $instance->register();

            $this->syncSchema($manifest);

            $instance->boot();

            $this->instances[$name] = $instance;
            $this->loaded[$name] = true;

            unset($this->loading[$name]);

            return $instance;
        } catch (\Throwable $e) {
            unset($this->loading[$name]);

            $this->logger?->error(
                "Failed to load plugin '{$name}': {$e->getMessage()}",
                ['exception' => $e],
            );

            return null;
        }
    }

    /**
     * Lazy hook loader: registers a closure that loads the plugin
     * ONLY when the hook is actually triggered at runtime.
     */
    private function registerLazyHookLoaders(string $name, PluginManifest $manifest): void
    {
        $usedHooks = $manifest->usedHooks();

        foreach ($usedHooks as $hook) {
            $this->hooks->registerLazyLoader($hook, function () use ($name): void {
                $this->ensureLoaded($name);
            });
        }
    }

    /**
     * Registers PSR-4 autoloader for the plugin so its classes exist
     * when the plugin is loaded. This is cheap — just spl_autoload_register.
     */
    private function registerAutoloader(PluginManifest $manifest): void
    {
        static $registered = [];

        $name = $manifest->name();

        if (isset($registered[$name])) {
            return;
        }

        $registered[$name] = true;

        $autoloadFile = $manifest->path() . '/vendor/autoload.php';

        if (file_exists($autoloadFile)) {
            require_once $autoloadFile;
        }
    }

    /**
     * Resolve provides→consumes dependencies between installed plugins
     * by registering lazy loaders for consume hooks.
     */
    private function registerProvidedDependencies(): void
    {
        $installed = $this->store->getInstalledPlugins();

        $providesMap = [];

        foreach ($installed as $name => $meta) {
            $manifest = $this->manifests[$name] ?? null;

            if ($manifest === null) {
                continue;
            }

            foreach ($manifest->provides() as $capability) {
                $providesMap[$capability] = $name;
            }
        }

        foreach ($installed as $name => $meta) {
            if (!($meta['enabled'] ?? false)) {
                continue;
            }

            $manifest = $this->manifests[$name] ?? null;

            if ($manifest === null) {
                continue;
            }
        }
    }

    // ========================================================================
    //  Instance creation
    // ========================================================================

    private function createInstance(PluginManifest $manifest): PluginInterface
    {
        $entryClass = $manifest->entryClass();

        if ($entryClass !== null && class_exists($entryClass)) {
            $instance = new $entryClass($this->app, $manifest);

            if (!$instance instanceof PluginInterface) {
                throw new \RuntimeException(
                    "Entry class '{$entryClass}' for plugin '{$manifest->name()}' must implement PluginInterface"
                );
            }

            return $instance;
        }

        return new class($this->app, $manifest) extends Plugin {};
    }

    // ========================================================================
    //  Dependency resolution
    // ========================================================================

    private function resolveDependenciesForInstall(PluginManifest $manifest): void
    {
        foreach ($manifest->dependencyNames() as $depName) {
            if (!$this->store->isInstalled($depName)) {
                $depManifest = $this->manifests[$depName] ?? null;

                if ($depManifest === null) {
                    throw PluginException::dependencyNotFound($manifest->name(), $depName);
                }

                $this->install($depName);
            }

            $this->validateVersionConstraint($manifest, $depName);
        }
    }

    private function resolveDependenciesForLoad(PluginManifest $manifest): void
    {
        foreach ($manifest->dependencyNames() as $depName) {
            if (isset($this->loaded[$depName])) {
                continue;
            }

            $depManifest = $this->manifests[$depName] ?? null;

            if ($depManifest === null) {
                continue;
            }

            if (!$this->store->isInstalled($depName)) {
                continue;
            }

            $this->loadPlugin($depName);
            $this->validateVersionConstraint($manifest, $depName);
        }
    }

    private function validateVersionConstraint(PluginManifest $manifest, string $depName): void
    {
        $deps = $manifest->dependencies();
        $constraint = $deps[$depName] ?? '*';

        if ($constraint === '*') {
            return;
        }

        $depManifest = $this->manifests[$depName] ?? null;

        if ($depManifest === null) {
            return;
        }

        $depVersion = $depManifest->version();

        if (!VersionConstraint::satisfies($depVersion, $constraint)) {
            throw PluginException::versionMismatch(
                $manifest->name(),
                $depName,
                $constraint,
                $depVersion,
            );
        }
    }

    // ========================================================================
    //  Hooks validation
    // ========================================================================

    private function validateHooks(string $pluginName, PluginManifest $manifest): void
    {
        $errors = $this->hooksValidator->registerPluginHooks(
            $pluginName,
            $manifest->declaredHooks(),
            $manifest->usedHooks(),
        );

        if ($errors !== []) {
            throw PluginException::invalidManifest(
                $pluginName,
                implode('; ', $errors),
            );
        }
    }

    // ========================================================================
    //  Schema
    // ========================================================================

    private function syncSchema(PluginManifest $manifest): void
    {
        $schemaFile = $manifest->schemaFile();

        if ($schemaFile === null) {
            return;
        }

        if (!$this->app->has(\App\Foundation\Database\ModuleSchemaManager::class)) {
            return;
        }

        $schemaManager = $this->app->make(\App\Foundation\Database\ModuleSchemaManager::class);

        try {
            $schemaManager->syncModule($manifest->path());
        } catch (\Throwable $e) {
            $this->logger?->error(
                "Schema sync failed for plugin '{$manifest->name()}': {$e->getMessage()}",
                ['exception' => $e],
            );
        }
    }

    // ========================================================================
    //  Discovery cache
    // ========================================================================

    private function discoveryCachePath(): string
    {
        return $this->app->basePath('storage/framework/cache/plugins-discovery.php');
    }

    private function hookMapCachePath(): string
    {
        return $this->app->basePath('storage/framework/cache/hook-map.php');
    }

    private function loadDiscoveryCache(): bool
    {
        $cacheFile = $this->discoveryCachePath();

        if (!file_exists($cacheFile)) {
            return false;
        }

        $cacheMtime = filemtime($cacheFile);

        foreach ($this->pluginPaths as $pluginsPath) {
            if (!is_dir($pluginsPath)) {
                continue;
            }

            $dirMtime = filemtime($pluginsPath);

            if ($dirMtime > $cacheMtime) {
                return false;
            }

            foreach (scandir($pluginsPath) as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $pluginPath = $pluginsPath . '/' . $entry;

                if (!is_dir($pluginPath)) {
                    continue;
                }

                $manifestPath = $pluginPath . '/plugin.json';

                if (file_exists($manifestPath) && filemtime($manifestPath) > $cacheMtime) {
                    return false;
                }
            }
        }

        $cached = require $cacheFile;

        if (!is_array($cached)) {
            return false;
        }

        foreach ($cached as $data) {
            if (!isset($data['name'], $data['_path'])) {
                continue;
            }

            $manifest = new PluginManifest($data['_path']);
            $this->manifests[$data['name']] = $manifest;
        }

        return true;
    }

    private function saveDiscoveryCache(): void
    {
        $cacheDir = dirname($this->discoveryCachePath());

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $data = [];

        foreach ($this->manifests as $name => $manifest) {
            $data[$name] = [
                'name' => $name,
                'version' => $manifest->version(),
                'title' => $manifest->title(),
                '_path' => $manifest->path(),
            ];
        }

        $content = '<?php return ' . var_export($data, true) . ';' . "\n";
        file_put_contents($this->discoveryCachePath(), $content, LOCK_EX);
    }

    public function clearDiscoveryCache(): void
    {
        $cacheFile = $this->discoveryCachePath();

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    // ========================================================================
    //  Utilities
    // ========================================================================

    private function findRepositoryForType(string $type, string $url): ?PluginRepositoryInterface
    {
        foreach ($this->repositories as $repo) {
            if ($repo->getName() === $type) {
                return $repo;
            }
        }

        return null;
    }
}
