<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme;

use Witals\Framework\Module\Module as WitalsModule;

class Module extends WitalsModule
{
    public function register(): void
    {
        $themePath = $this->resolveThemePath();

        $this->app->singleton(StyleParser::class, function () use ($themePath) {
            return new StyleParser($themePath . '/style.css');
        });

        $this->app->singleton(FunctionsLoader::class, function () use ($themePath) {
            return new FunctionsLoader($themePath);
        });

        $this->app->singleton(TemplateHierarchy::class, function () {
            return new TemplateHierarchy();
        });

        $this->app->singleton(TemplateLoader::class, function ($app) use ($themePath) {
            return new TemplateLoader(
                $themePath,
                $app->make(FunctionsLoader::class),
                $app->make(TemplateHierarchy::class),
            );
        });

        $this->app->singleton(ClassicThemeEngine::class, function ($app) use ($themePath) {
            return new ClassicThemeEngine(
                $themePath,
                $app,
            );
        });

        // Register the resetter so the framework Kernel can call reset() via ResettableInterface
        // without knowing any PrestoWorld-specific class names.
        $this->app->singleton(TransformerRegistryResetter::class);
    }

    public function boot(): void
    {
        $this->registerConsoleCommand();

        $transformerDir = __DIR__ . '/Transformers';
        if (is_dir($transformerDir)) {
            TransformerRegistry::registerFromDirectory($transformerDir);
        }
    }

    private function registerConsoleCommand(): void
    {
        if (!$this->app->has(\Witals\Framework\Console\Kernel::class)) {
            return;
        }

        $this->app->make(\Witals\Framework\Console\Kernel::class)
            ->register(\PrestoWorld\Modules\ClassicTheme\Console\GenerateStubsCommand::class);
    }

    private function resolveThemePath(): string
    {
        $envPath = getenv('PW_THEME_DIR');
        if ($envPath) {
            return $envPath;
        }

        $active = $this->app->config('theme.active', 'jankx');
        return $this->app->basePath() . '/public/wp-content/themes/' . $active;
    }
}
