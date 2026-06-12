<?php

declare(strict_types=1);

namespace App\Foundation;

use Witals\Framework\Application as BaseApplication;
use PrestoWorld\Foundation\Config\ConfigRepository;

class Application extends BaseApplication
{
    protected ?ConfigRepository $configRepository = null;

    public function registerConfiguredProviders(): void
    {
        parent::registerConfiguredProviders();

        $this->singleton(
            \Witals\Framework\Contracts\Http\Kernel::class,
            \App\Http\Kernel::class
        );

        $this->singleton(
            \App\Contracts\Http\PageRenderer::class,
            \App\Http\PageRenderer::class,
        );

        $this->singleton(\App\Http\TemplateResolver::class, function () {
            return new \App\Http\TemplateResolver(
                mapping: $this->config('templates.mapping', []),
                defaultTemplate: $this->config('templates.default', 'index'),
            );
        });

        $this->singleton(
            \App\Services\ContentRenderer::class,
        );

        $this->singleton(
            \App\Services\PageService::class,
        );

        $this->singleton(\App\Http\PageRenderer::class, function () {
            return new \App\Http\PageRenderer(
                defaultTitle: $this->config('theme.default_title', 'PrestoWorld'),
                charset: $this->config('theme.charset', 'UTF-8'),
                viewport: $this->config('theme.viewport', 'width=device-width, initial-scale=1.0'),
                cssReset: $this->config('theme.css_reset', '*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; } body { font-family: system-ui, sans-serif; line-height: 1.6; }'),
            );
        });

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
