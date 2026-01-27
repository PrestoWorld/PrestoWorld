<?php

declare(strict_types=1);

namespace App\Foundation\Theme;

use App\Foundation\Application;
use App\Foundation\Theme\Contracts\ThemeInterface;

class ThemeManager
{
    protected Application $app;
    protected array $themes = [];
    protected ?string $activeTheme = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Discover themes in the themes directory
     */
    public function discover(): void
    {
        $themesPath = $this->app->basePath('themes');
        
        if (!is_dir($themesPath)) {
            return;
        }

        foreach (scandir($themesPath) as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $themePath = $themesPath . '/' . $dir;
            $metadataPath = $themePath . '/theme.json';

            if (file_exists($metadataPath)) {
                $metadata = json_decode(file_get_contents($metadataPath), true);
                $this->themes[$metadata['name']] = new Theme($this->app, $themePath, $metadata);
            }
        }
    }

    /**
     * Set the active theme
     */
    public function setActiveTheme(string $name): void
    {
        if (isset($this->themes[$name])) {
            $this->activeTheme = $name;
            $this->themes[$name]->boot();
        }
    }

    /**
     * Get the active theme instance
     */
    public function getActiveTheme(): ?ThemeInterface
    {
        return $this->themes[$this->activeTheme] ?? null;
    }

    /**
     * Render a view from the active theme
     */
    public function render(string $view, array $data = []): string
    {
        $theme = $this->getActiveTheme();
        if (!$theme) {
            return "No active theme.";
        }

        $viewPath = $theme->getPath() . '/src/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            return "View [{$view}] not found in theme [{$theme->getName()}].";
        }

        extract($data);
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }

    public function all(): array
    {
        return $this->themes;
    }
}
