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
use PrestoWorld\Modules\Gutenberg\Renderer\Decorators\LayoutDecorator;
use PrestoWorld\Modules\Gutenberg\Renderer\Decorators\StyleDecorator;
use PrestoWorld\Modules\Schema\PostRepository;

class Module extends WitalsModule
{
    public function register(): void
    {
        $this->app->singleton(BlockParser::class, function () {
            return new BlockParser();
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
                'site_title' => 'PrestoWorld',
                'site_url' => '/',
                'post_repository' => $app->make(PostRepository::class)
            ]);

            // Register Decorators for attribute processing (Decorator Pattern)
            $renderer->addDecorator(new LayoutDecorator());
            $renderer->addDecorator(new StyleDecorator());
            
            return $renderer;
        });

        $this->app->singleton(ThemeJson::class, function ($app) {
            $themePath = $this->getThemePath($app);
            return new ThemeJson($themePath . '/theme.json');
        });
    }

    public function boot(): void
    {
        $parser   = $this->app->make(BlockParser::class);
        $renderer = $this->app->make(BlockRenderer::class);
        $patterns = $this->app->make(PatternRegistry::class);

        // Core Rerender logic for template parts and patterns
        $renderer->register('__rerender', function (string $html) use ($parser, $renderer): string {
            $blocks = $parser->parse($html);
            return $renderer->render($blocks);
        });

        // Pre-warm patterns in persistent environments (RoadRunner)
        if (isset($_SERVER['RR_MODE']) || isset($_SERVER['FRANKENPHP_WORKER'])) {
            $patterns->discover();
        }
    }

    public function renderTemplate(string $template): string
    {
        $themePath = $this->getThemePath($this->app);
        $path = $themePath . "/templates/{$template}.html";
        
        if (!file_exists($path)) {
            throw new \Exception("Template not found: {$template}");
        }

        $html = file_get_contents($path);
        $parser = $this->app->make(BlockParser::class);
        $renderer = $this->app->make(BlockRenderer::class);

        $blocks = $parser->parse($html);
        return $renderer->render($blocks);
    }

    public function getStyles(): string
    {
        $themeJson = $this->app->make(ThemeJson::class);
        return $themeJson->compile();
    }

    protected function getThemePath($app): string
    {
        return $app->basePath() . '/content/themes/twentytwentyfive';
    }
}
