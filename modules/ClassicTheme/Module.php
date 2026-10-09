<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme;

use Witals\Framework\Module\Module as WitalsModule;

class Module extends WitalsModule
{
    public function register(): void
    {
        // The active theme path is resolved lazily so the classic engine is only
        // initialised when a legacy (pre-Gutenberg, PHP-template) theme is active.
        $this->app->singleton(StyleParser::class, function () {
            return new StyleParser($this->resolveThemePath() . '/style.css');
        });

        $this->app->singleton(FunctionsLoader::class, function () {
            return new FunctionsLoader($this->resolveThemePath());
        });

        $this->app->singleton(TemplateHierarchy::class, function () {
            return new TemplateHierarchy();
        });

        $this->app->singleton(TemplateLoader::class, function ($app) {
            return new TemplateLoader(
                $this->resolveThemePath(),
                $app->make(FunctionsLoader::class),
                $app->make(TemplateHierarchy::class),
            );
        });

        $this->app->singleton(ClassicThemeEngine::class, function ($app) {
            return new ClassicThemeEngine(
                $this->resolveThemePath(),
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
