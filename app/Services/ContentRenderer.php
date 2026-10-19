<?php

declare(strict_types=1);

namespace App\Services;

use PrestoWorld\Modules\ClassicTheme\ClassicThemeEngine;
use PrestoWorld\Modules\ClassicTheme\ThemeDetector;
use Presto\Context\Loader as ContextLoader;
use App\Contracts\Services\ContentRenderer as ContentRendererContract;
use App\Contracts\Services\RenderedContent;
use PrestoWorld\Core\AssetManager;
use PrestoWorld\Gutenberg\Gutenberg;
use Witals\Framework\Contracts\Container;

class ContentRenderer implements ContentRendererContract
{
    private ?ClassicThemeEngine $classicEngine = null;

    private bool $resolved = false;

    private bool $enqueueScriptsDone = false;

    public function __construct(
        private ContextLoader $contextLoader,
        private Container $container,
        private string $themePath = '',
    ) {}

    public function render(string $template, array $post = []): RenderedContent
    {
        $classic = $this->classicEngine();
        if ($classic !== null) {
            return $classic->render($template, $post);
        }

        // Block theme: let the theme enqueue its assets (style.css, ...).
        $this->dispatchEnqueueScripts();

        $body = $this->contextLoader->renderTemplate($template, $post);
        $styles = $this->collectStyles();

        // Partial body + styles — HtmlComposer wraps the full document.
        return new RenderedContent($body, $styles, complete: false);
    }

    public function supports(string $template): bool
    {
        $classic = $this->classicEngine();
        if ($classic !== null) {
            return $classic->supports($template);
        }

        return $this->contextLoader->supports($template);
    }

    private function dispatchEnqueueScripts(): void
    {
        if ($this->enqueueScriptsDone) {
            return;
        }

        $this->enqueueScriptsDone = true;

        if (function_exists('do_action')) {
            do_action('wp_enqueue_scripts');
        }
    }

    /**
     * theme.json/base CSS from the Gutenberg kernel + theme-queued styles
     * (wp_enqueue_style → AssetManager) inlined into <style> blocks.
     */
    private function collectStyles(): string
    {
        $css = '';

        $kernel = Gutenberg::kernel();

        if ($kernel !== null) {
            $css .= '<style id="gutenberg-theme-css">' . "\n"
                . $kernel->themeCss() . "\n</style>\n";
        }

        foreach (AssetManager::queuedStyles() as $handle => $asset) {
            $inline = $this->inlineStyleContent((string) ($asset['src'] ?? ''));

            if ($inline !== null) {
                $css .= '<style id="' . htmlspecialchars($handle, ENT_QUOTES) . '-inline-css">' . "\n"
                    . $inline . "\n</style>\n";
                continue;
            }

            $src = (string) ($asset['src'] ?? '');

            if ($src !== '') {
                $css .= '<link rel="stylesheet" id="'
                    . htmlspecialchars($handle, ENT_QUOTES)
                    . '-css" href="' . htmlspecialchars($src, ENT_QUOTES) . "\" />\n";
            }
        }

        return $css;
    }

    /**
     * Resolve a queued stylesheet URL to a local file and return its contents.
     */
    private function inlineStyleContent(string $src): ?string
    {
        if ($src === '') {
            return null;
        }

        $themeDir = $this->themePath !== '' ? rtrim($this->themePath, '/') : null;

        // Strip origin + query to map a URL back onto the local path.
        $path = (string) preg_replace('#^[a-z]+://[^/]+#i', '', $src);
        $path = (string) preg_replace('/\?.*$/', '', $path);

        if ($themeDir !== null && $path !== '') {
            $candidate = $themeDir . $path;

            if (str_starts_with($path, '/') && is_file($candidate)) {
                $contents = file_get_contents($candidate);

                return $contents === false ? null : $contents;
            }
        }

        if (is_file($src)) {
            $contents = file_get_contents($src);

            return $contents === false ? null : $contents;
        }

        return null;
    }

    /**
     * Resolve the classic engine only when the active theme is a legacy
     * (pre-Gutenberg, PHP-template) WordPress theme. New/block themes are
     * rendered by the ContextBuilder ContextLoader (the primary template engine).
     */
    private function classicEngine(): ?ClassicThemeEngine
    {
        if ($this->resolved) {
            return $this->classicEngine;
        }

        $this->resolved = true;

        if ($this->themePath === '' || !ThemeDetector::isClassic($this->themePath)) {
            return null;
        }

        if ($this->container->has(ClassicThemeEngine::class)) {
            $engine = $this->container->make(ClassicThemeEngine::class);
            if ($engine instanceof ClassicThemeEngine) {
                $this->classicEngine = $engine;
            }
        }

        return $this->classicEngine;
    }
}
