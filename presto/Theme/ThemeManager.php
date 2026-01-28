<?php

declare(strict_types=1);

namespace PrestoWorld\Theme;

use App\Foundation\Application;

class ThemeManager
{
    protected Application $app;
    protected array $themes = [];
    protected array $engines = [];
    protected ?Theme $activeTheme = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function discover(): void
    {
        // 1. Native Themes
        $this->scanDirectory($this->app->basePath('themes'));

        // 2. WordPress Themes
        $this->scanDirectory($this->app->basePath('public/wp-content/themes'));
    }

    protected function scanDirectory(string $path): void
    {
        if (!is_dir($path)) return;

        foreach (scandir($path) as $dir) {
            if ($dir === '.' || $dir === '..') continue;

            $themePath = $path . '/' . $dir;
            if (is_dir($themePath)) {
                $theme = new Theme($this->app, $themePath);
                $this->themes[$theme->getName()] = $theme;
            }
        }
    }

    public function setActiveTheme(string $name): void
    {
        if (isset($this->themes[$name])) {
            $this->activeTheme = $this->themes[$name];
            // Boot the theme (which boots its engine and helpers)
            $this->activeTheme->boot();
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

    public function render(string $view, array $data = []): string
    {
        if (!$this->activeTheme) {
            return "No active theme set.";
        }

        return $this->activeTheme->getEngine()->render($view, $data);
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
