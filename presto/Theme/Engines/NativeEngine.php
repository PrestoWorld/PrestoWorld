<?php

declare(strict_types=1);

namespace PrestoWorld\Theme\Engines;

use PrestoWorld\Theme\Native\ContextBuilder;
use PrestoWorld\Theme\Native\ContextLoader;

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
        // For Native, we use the theme's src/views directory
        $viewPath = $this->theme->getPath() . '/src/views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            $viewPath = $this->theme->getPath() . '/index.php';
        }

        if (file_exists($viewPath)) {
            extract($data);
            ob_start();
            include $viewPath;
            return ob_get_clean();
        }

        return "Native View Not Found: " . $view;
    }

    public function getTemplateEngineName(): string
    {
        return 'PHP';
    }

    protected function bootEngineHelpers(): void
    {
        // Boot native engine specific helpers
    }
}
