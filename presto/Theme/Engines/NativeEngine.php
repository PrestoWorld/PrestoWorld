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
        // Support template hierarchy - try multiple paths
        $possiblePaths = [
            $this->theme->getPath() . '/src/views/' . $view . '.php',
            $this->theme->getPath() . '/resources/views/' . $view . '.php',
            $this->theme->getPath() . '/' . $view . '.php',
        ];

        // Find the first existing template
        $viewPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $viewPath = $path;
                break;
            }
        }

        // Fallback to index.php if no specific template found
        if (!$viewPath) {
            $indexPaths = [
                $this->theme->getPath() . '/src/views/index.php',
                $this->theme->getPath() . '/index.php',
            ];
            foreach ($indexPaths as $path) {
                if (file_exists($path)) {
                    $viewPath = $path;
                    break;
                }
            }
        }

        if ($viewPath && file_exists($viewPath)) {
            extract($data);
            
            ob_start();
            try {
                include $viewPath;
                return ob_get_clean() ?: '';
            } catch (\Throwable $e) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                throw $e;
            }
        }


        throw new \RuntimeException("Native View Not Found: " . $view);
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
