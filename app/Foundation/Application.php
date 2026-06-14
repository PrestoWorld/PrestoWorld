<?php

declare(strict_types=1);

namespace App\Foundation;

use Witals\Framework\Application as BaseApplication;
use PrestoWorld\Foundation\Config\ConfigRepository;

class Application extends BaseApplication
{
    protected ?ConfigRepository $configRepository = null;

    public function registerCoreContainerAliases(): void
    {
        parent::registerCoreContainerAliases();

        $this->instance(self::class, $this);
    }

    public function registerConfiguredProviders(): void
    {
        parent::registerConfiguredProviders();

        // Register routing — binds RouteRegistry, Router and loads routes/web.php
        $this->register(\App\Providers\RouteServiceProvider::class);

        $this->singleton(
            \Witals\Framework\Contracts\Http\Kernel::class,
            \App\Http\Kernel::class
        );

        $this->singleton(
            \App\Contracts\Http\PageRenderer::class,
            \App\Http\PageRenderer::class,
        );

        $this->singleton(
            \App\Contracts\Services\HtmlComposer::class,
            \App\Services\HtmlComposer::class,
        );

        $this->singleton(\App\Contracts\Http\ThemeConfig::class, function () {
            return \App\Contracts\Http\ThemeConfig::fromArray($this->config('theme', []));
        });

        $this->singleton(\App\Http\TemplateResolver::class, function ($app) {
            $mapping = $app->config('templates.mapping', []);
            $default = $app->config('templates.default', 'index');
            return new \App\Http\TemplateResolver(
                new \App\Http\Mappings\ConfigMappingPolicy($mapping, $default),
            );
        });

        $this->singleton(
            \App\Contracts\Services\ContentRenderer::class,
            function ($app) {
                if ($app->has(\PrestoWorld\Modules\Gutenberg\Module::class)) {
                    return new \App\Services\ContentRenderer(
                        $app->make(\PrestoWorld\Modules\Gutenberg\Module::class),
                    );
                }
                return new \App\Services\NullContentRenderer();
            },
        );

        $this->singleton(
            \App\Services\PageService::class,
        );

        $this->register(\Witals\Framework\Auth\AuthServiceProvider::class);
        $this->register(\PrestoWorld\Foundation\Providers\DatabaseServiceProvider::class);

        // Persist compiled config cache for long-running environments
        if ($this->isLongRunning() && $this->configRepository !== null) {
            $this->configRepository->persistCache();
        }
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Register module routes before parent::boot() so they get PRIORITY_MODULE (checked first)
        if ($this->has(\App\Http\Routing\Contracts\RouteRegistryInterface::class)) {
            $registry = $this->make(\App\Http\Routing\Contracts\RouteRegistryInterface::class);

            if ($this->has(\Witals\Framework\Module\ModuleManager::class)) {
                $witalsManager = $this->make(\Witals\Framework\Module\ModuleManager::class);
                $witalsManager->registerModuleRoutes($registry);
            }

            // Admin auth only applies to admin routes — lazy-load
            $registry->addMiddlewareFor(
                \App\Http\Middleware\AdminAuthMiddleware::class,
                only: ['/dashboard', '/api/admin'],
            );
            // Global middleware runs on all matched routes
            $registry->addMiddleware(\App\Http\Middleware\CorsMiddleware::class);
            $registry->addMiddleware(\App\Http\Middleware\LocaleMiddleware::class);
        }

        parent::boot();
    }

    public function setConfigPaths(string|array $paths): void
    {
        $paths = is_array($paths) ? $paths : [$paths];
        $this->configRepository = null;
        $repo = $this->resolveConfigRepository();
        $repo->setPaths(
            array_map(fn (string $p) => $this->resolveConfigPath($p), $paths)
        );
    }

    public function addConfigPath(string $path): void
    {
        $repo = $this->resolveConfigRepository();
        $repo->addPath($this->resolveConfigPath($path));
    }

    public function config(string $key, $default = null)
    {
        $repo = $this->resolveConfigRepository();

        $keys = explode('.', $key);
        $file = array_shift($keys);

        $config = $repo->load($file);
        if ($config === null) {
            return $default;
        }

        foreach ($keys as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    public function resolveConfigRepository(): ConfigRepository
    {
        if ($this->configRepository === null) {
            $this->configRepository = new ConfigRepository(
                $this->resolveConfigPath('config')
            );
            $this->configRepository->setCachePath(
                $this->basePath('storage/framework/cache/config.php')
            );
        }

        return $this->configRepository;
    }

    private function resolveConfigPath(string $path): string
    {
        return str_starts_with($path, '/') || str_starts_with($path, '.')
            ? $path
            : $this->basePath($path);
    }

    protected function initializeTranslator(): void
    {
        if ($this->translator === null) {
            $locale = $this->config('app.locale', getenv('APP_LOCALE') ?: 'en');
            $paths = [
                $this->basePath('resources/lang'),
            ];

            $this->translator = new \Witals\Framework\I18n\Translator($locale, $paths);

            $this->instance(\Witals\Framework\Contracts\I18n\TranslatorFactory::class, $this->translator);
            $this->instance('translator', $this->translator);
        }
    }
}
