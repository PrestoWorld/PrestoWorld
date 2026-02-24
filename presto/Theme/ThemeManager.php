<?php

declare(strict_types=1);

namespace PrestoWorld\Theme;

use App\Foundation\Application;

class ThemeManager
{
    protected Application $app;
    protected array $themes = [];
    protected array $engines = [];
    protected array $discoveryPaths = [];
    protected array $scannedPaths = [];
    protected ?Theme $activeTheme = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function discover(): void
    {
        foreach ($this->discoveryPaths as $path) {
            $this->scanDirectory($path);
        }
    }

    /**
     * Add a directory to scan for themes.
     */
    public function addDiscoveryPath(string $path): void
    {
        if (!in_array($path, $this->discoveryPaths)) {
            $this->discoveryPaths[] = $path;
        }
    }

    protected function scanDirectory(string $path): void
    {
        if (!is_dir($path)) return;

        // Skip if already scanned
        if (isset($this->scannedPaths[$path])) return;

        foreach (scandir($path) as $dir) {
            if ($dir === '.' || $dir === '..') continue;

            $themePath = $path . '/' . $dir;
            if (is_dir($themePath)) {
                $theme = new Theme($this->app, $themePath);
                $this->themes[$theme->getName()] = $theme;
                // error_log("Discovered theme: " . $theme->getName() . " at " . $themePath);
            }
        }

        $this->scannedPaths[$path] = true;
    }

    public function setActiveTheme(string $name): void
    {
        if (isset($this->themes[$name])) {
            $this->activeTheme = $this->themes[$name];
            // Boot the theme (which boots its engine and helpers)
            $this->activeTheme->boot();
        } else {
            // error_log("Failed to set active theme: {$name}. Available themes: " . implode(', ', array_keys($this->themes)));
        }
    }

    public function loadActiveTheme(): void
    {
        if ($this->activeTheme) {
            $this->activeTheme->load();
        }
    }

    public function getActiveTheme(): ?Theme
    {
        return $this->activeTheme;
    }

    public function render(string|array $view, array $data = []): string
    {
        if (!$this->activeTheme) {
            $this->setActiveTheme('tucnguyen');
        }

        if (!$this->activeTheme) {
            return "No active theme set.";
        }

        $views = is_array($view) ? $view : [$view];
        $engine = $this->activeTheme->getEngine();

        foreach ($views as $v) {
            try {
                return $engine->render($v, $data);
            } catch (\Throwable $e) {
                // If it's a "View Not Found" error, continue. Otherwise throw.
                if (strpos($e->getMessage(), 'View Not Found') !== false) {
                    continue;
                }
                throw $e;
            }
        }

        throw new \RuntimeException("None of the templates found: " . implode(', ', $views));
    }

    public function all(): array
    {
        return $this->themes;
    }

    public function registerEngine(string $type, string $engineClass): void
    {
        $this->engines[$type] = $engineClass;
    }

    public function resolveEngine(Theme $theme): \PrestoWorld\Theme\Engines\AbstractEngine
    {
        $type = $theme->getType();
        
        if (isset($this->engines[$type])) {
            $class = $this->engines[$type];
            return new $class($theme);
        }

        // Native is the only core engine
        if ($type === \PrestoWorld\Theme\ThemeType::NATIVE->value) {
            return new \PrestoWorld\Theme\Engines\NativeEngine($theme);
        }

        throw new \RuntimeException("No theme engine registered for type: {$type}");
    }
}
