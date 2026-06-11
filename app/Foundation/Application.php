<?php

declare(strict_types=1);

namespace App\Foundation;

use Witals\Framework\Application as BaseApplication;
use PrestoWorld\Foundation\Config\ConfigRepository;

/**
 * PrestoWorld Application
 */
class Application extends BaseApplication
{
    protected array $config = [];
    protected ?ConfigRepository $configRepository = null;

    /**
     * Register configured service providers
     */
    public function registerConfiguredProviders(): void
    {
        parent::registerConfiguredProviders();

        // Bind the HTTP Kernel
        $this->singleton(
            \Witals\Framework\Contracts\Http\Kernel::class,
            \App\Http\Kernel::class
        );

        // Bind Core Database
        $this->register(\PrestoWorld\Foundation\Providers\DatabaseServiceProvider::class);
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        parent::boot();

        // Register PrestoWorld Core Modules
        $this->registerModule(\PrestoWorld\Modules\Gutenberg\Module::class);
        $this->registerModule(\PrestoWorld\Modules\Schema\Module::class);
        $this->registerModule(\PrestoWorld\Modules\Search\Module::class);
    }

    protected function registerModule(string $moduleClass): void
    {
        $module = new $moduleClass($this);
        $module->register();
        $module->boot();
    }

    /**
     * Set config path(s) for the application.
     */
    public function setConfigPaths(string|array $paths): void
    {
        $paths = is_array($paths) ? $paths : [$paths];
        $this->resolveConfigRepository();
        $this->configRepository->setPaths(
            array_map(fn (string $p) => $this->resolveConfigPath($p), $paths)
        );
        $this->config = [];
    }

    /**
     * Add an additional config path.
     */
    public function addConfigPath(string $path): void
    {
        $this->resolveConfigRepository();
        $this->configRepository->addPath($this->resolveConfigPath($path));
        $this->config = [];
    }

    /**
     * Get config value with dot notation.
     */
    public function config(string $key, $default = null)
    {
        $this->resolveConfigRepository();

        $keys = explode('.', $key);
        $file = array_shift($keys);

        if (isset($this->config[$file])) {
            $config = $this->config[$file];
        } else {
            $config = $this->configRepository->load($file);
            if ($config === null) {
                return $default;
            }
            $this->config[$file] = $config;
        }

        foreach ($keys as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    private function resolveConfigRepository(): void
    {
        if ($this->configRepository === null) {
            $this->configRepository = new ConfigRepository(
                $this->resolveConfigPath('config')
            );
        }
    }

    private function resolveConfigPath(string $path): string
    {
        return str_starts_with($path, '/') || str_starts_with($path, '.')
            ? $path
            : $this->basePath($path);
    }
}
