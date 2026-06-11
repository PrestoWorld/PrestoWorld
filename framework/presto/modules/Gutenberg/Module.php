<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg;

use Witals\Framework\Module\Module as WitalsModule;
use PrestoWorld\Modules\Gutenberg\Parser\BlockParser;
use PrestoWorld\Modules\Gutenberg\Renderer\BlockRenderer;
use PrestoWorld\Modules\Gutenberg\Theme\ThemeJson;
use PrestoWorld\Modules\Gutenberg\Pattern\PatternRegistry;
use PrestoWorld\Modules\Gutenberg\Pattern\MemoryStorage;
use PrestoWorld\Modules\Gutenberg\Pattern\FileCacheStorage;

class Module extends WitalsModule
{
    public function __construct($app)
    {
        parent::__construct($app, __DIR__, ['name' => 'gutenberg']);
    }

    public function getName(): string
    {
        return 'Gutenberg Native Engine';
    }

    public function register(): void
    {
        $this->app->singleton(BlockParser::class, fn() => new BlockParser());

        $this->app->singleton(ThemeJson::class, function ($app) {
            $themePath = $this->getThemePath($app);
            return new ThemeJson($themePath);
        });

        $this->app->singleton(PatternRegistry::class, function ($app) {
            $themePath = $this->getThemePath($app);
            
            // Detect runtime and choose strategy
            $isStateful = isset($_SERVER['RR_MODE']) || isset($_SERVER['FRANKENPHP_WORKER']);
            $storage = $isStateful 
                ? new MemoryStorage() 
                : new FileCacheStorage($app->basePath());

            $registry = new PatternRegistry($themePath);
            $registry->setStorage($storage);
            
            return $registry;
        });

        $this->app->singleton(BlockRenderer::class, function ($app) {
            $renderer = new BlockRenderer();
            $renderer->setPatternRegistry($app->make(PatternRegistry::class));
            $renderer->setContext([
                'theme_path' => $this->getThemePath($app),
                'site_title' => $app->config('app.name', 'PrestoWorld'),
                'site_tagline' => $app->config('app.tagline', 'High-Performance CMS'),
                'site_url' => $app->config('app.url', '/'),
                'site_logo' => $app->config('app.logo', ''),
            ]);

            // Register a __rerender callback so template-part and pattern
            // can recursively parse+render raw Gutenberg HTML
            $parser = $app->make(BlockParser::class);
            $renderer->register('__rerender', function (string $html) use ($parser, $renderer): string {
                $blocks = $parser->parse($html);
                return $renderer->render($blocks);
            });

            return $renderer;
        });
    }

    public function boot(): void
    {
        // For RoadRunner: Pre-warm the pattern registry to keep everything in RAM
        /** @var PatternRegistry $patterns */
        $patterns = $this->app->make(PatternRegistry::class);
        $patterns->discover();
    }

    /**
     * Render a full template by name (e.g. 'index', 'single', 'archive')
     */
    public function renderTemplate(string $templateName): string
    {
        $themePath = $this->getThemePath($this->app);
        $path = "{$themePath}/templates/{$templateName}.html";

        if (!file_exists($path)) {
            return "<!-- Template not found: {$templateName} -->";
        }

        $content = file_get_contents($path);
        return $this->rerender($content);
    }

    /**
     * Get compiled theme CSS
     */
    public function getStyles(): string
    {
        return $this->app->make(ThemeJson::class)->compile();
    }

    /**
     * Re-render raw Gutenberg HTML through the engine
     */
    public function rerender(string $html): string
    {
        $parser   = $this->app->make(BlockParser::class);
        $renderer = $this->app->make(BlockRenderer::class);
        $blocks   = $parser->parse($html);
        return $renderer->render($blocks);
    }

    protected function getThemePath($app): string
    {
        $activeTheme = $app->config('theme.active', 'twentytwentyfive');
        return $app->basePath("content/themes/{$activeTheme}");
    }
}
