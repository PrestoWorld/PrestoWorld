<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg;

use Witals\Framework\Module\BaseModule;
use PrestoWorld\Modules\Gutenberg\Parser\BlockParser;
use PrestoWorld\Modules\Gutenberg\Renderer\BlockRenderer;
use PrestoWorld\Modules\Gutenberg\Theme\ThemeJson;

class Module extends BaseModule
{
    public function getName(): string
    {
        return 'Gutenberg Native Engine';
    }

    public function register(): void
    {
        $this->app->singleton(BlockParser::class, fn() => new BlockParser());
        $this->app->singleton(BlockRenderer::class, fn() => new BlockRenderer());
        
        $this->app->singleton(ThemeJson::class, function($app) {
            $activeTheme = $app->config('theme.active', 'twentytwentyfive');
            $path = $app->basePath("content/themes/{$activeTheme}/theme.json");
            return new ThemeJson($path);
        });
    }

    public function boot(): void
    {
        $renderer = $this->app->make(BlockRenderer::class);
        
        // Register some core block placeholders for MVP
        $renderer->register('core/paragraph', function($attrs, $content) {
            return "<p>{$content}</p>";
        });

        $renderer->register('core/heading', function($attrs, $content) {
            $level = $attrs['level'] ?? 2;
            return "<h{$level}>{$content}</h{$level}>";
        });

        // Template Part support
        $renderer->register('core/template-part', function($attrs) {
            $slug = $attrs['slug'] ?? '';
            $activeTheme = $this->app->config('theme.active', 'twentytwentyfive');
            $path = $this->app->basePath("content/themes/{$activeTheme}/parts/{$slug}.html");
            
            if (file_exists($path)) {
                return $this->renderRawContent(file_get_contents($path));
            }
            return "<!-- Template part not found: {$slug} -->";
        });

        // Pattern support
        $renderer->register('core/pattern', function($attrs) {
            $slug = $attrs['slug'] ?? '';
            // Pattern logic normally involves looking up registered patterns or files
            return "<!-- Pattern placeholder: {$slug} -->";
        });
    }

    /**
     * Render raw Gutenberg content
     */
    public function renderRawContent(string $content): string
    {
        $parser = $this->app->make(BlockParser::class);
        $renderer = $this->app->make(BlockRenderer::class);

        $blocks = $parser->parse($content);
        return $renderer->render($blocks);
    }

    /**
     * Get the generated CSS for the active theme
     */
    public function getStyles(): string
    {
        return $this->app->make(ThemeJson::class)->generateCss();
    }

    /**
     * Render a template file from the theme
     */
    public function renderTemplate(string $templateName): string
    {
        $activeTheme = $this->app->config('theme.active', 'twentytwentyfive');
        $path = $this->app->basePath("content/themes/{$activeTheme}/templates/{$templateName}.html");
        
        if (!file_exists($path)) {
            return "Template {$templateName} not found in theme {$activeTheme}";
        }

        $content = file_get_contents($path);
        $parser = $this->app->make(BlockParser::class);
        $renderer = $this->app->make(BlockRenderer::class);

        $blocks = $parser->parse($content);
        return $renderer->render($blocks);
    }
}
