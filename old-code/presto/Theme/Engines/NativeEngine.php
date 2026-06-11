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
        $themeViewPath = $this->theme->getPath() . '/resources/views';
        if (is_dir($themeViewPath)) {
            $viewFactory->prependLocation($themeViewPath);
            foreach ($viewFactory->getEngines() as $engine) {
                if (method_exists($engine, 'prependPath')) {
                    $engine->prependPath($themeViewPath);
                }
            }
        }

        // 3. Detect view file type
        $variants = ['.' . $locale, ''];
        
        // 3. Detect and render view using the Factory
        foreach ($variants as $variant) {
            $viewName = $view . $variant;
            if ($viewFactory->exists($viewName)) {
                $this->theme->getApp()->instance('view.rendered', true);
                return $viewFactory->make($viewName, $data)->render();
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
