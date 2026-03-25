<?php

declare(strict_types=1);

namespace PrestoWorld\Theme\Engines;

use PrestoWorld\Theme\Native\ContextBuilder;
use PrestoWorld\Theme\Native\ContextLoader;
use Witals\Framework\Contracts\View\Factory as ViewFactory;

class NativeEngine extends AbstractEngine
{
    public function load(): void
    {
        $this->boot();

        // Native logic: use context builder and context loader
        $builder = new ContextBuilder($this->theme->getPath());
        $loader = new ContextLoader($this->theme, $builder);

        $loader->load();
    }

    public function render(string $view, array $data = []): string
    {
        // 1. Get View Factory
        $viewFactory = $this->theme->getApp()->make(ViewFactory::class);
        $locale = $this->theme->getApp()->translator()->getLocale();

        // 2. Register theme locations to View Factory if not done already
        // In PrestoWorld standardization, themes should use the core view factory
        $themeViewPath = $this->theme->getPath() . '/resources/views';
        // (Usually done in ServiceProvider but we ensure it here for theme-engine isolation)
        if (method_exists($viewFactory, 'addLocation')) {
            $viewFactory->addLocation($themeViewPath);
        }

        // 3. Detect view file type
        // Check for locale specific .stempler.php first, then .stempler.php, then .php
        $extensions = ['.stempler.php', '.php'];
        $variants = ['.' . $locale, ''];

        foreach ($variants as $variant) {
            foreach ($extensions as $ext) {
                $checkPath = $themeViewPath . '/' . $view . $variant . $ext;
                if (file_exists($checkPath)) {
                    // If it's a stempler file, use the engine
                    if (str_ends_with($checkPath, '.stempler.php')) {
                        $this->theme->getApp()->instance('view.rendered', true);
                        return $viewFactory->make($view . $variant, $data)->render();
                    }
                    
                    // Fallback to legacy include if it's plain .php but requested via NativeEngine
                    extract($data);
                    ob_start();
                    $this->theme->getApp()->instance('view.rendered', true);
                    try {
                        include $checkPath;
                        return ob_get_clean() ?: '';
                    } catch (\Throwable $e) {
                        if (ob_get_level() > 0) ob_end_clean();
                        throw $e;
                    }
                }
            }
        }

        throw new \RuntimeException("Native Engine: View Not Found: " . $view);
    }

    public function getTemplateEngineName(): string
    {
        return 'Stempler/PHP Hybrid';
    }

    protected function bootEngineHelpers(): void
    {
        // Boot native engine specific helpers
    }
}
