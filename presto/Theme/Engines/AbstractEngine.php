<?php

declare(strict_types=1);

namespace PrestoWorld\Theme\Engines;

use PrestoWorld\Theme\Theme;

abstract class AbstractEngine
{
    protected Theme $theme;
    protected bool $booted = false;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }

    /**
     * Load the theme using the engine's specific mechanism.
     */
    abstract public function load(): void;

    /**
     * Render a view using the engine.
     */
    abstract public function render(string $view, array $data = []): string;

    /**
     * Get the name of the template engine used (e.g. PHP, Stempler, etc.)
     */
    public function getTemplateEngineName(): string
    {
        return 'PHP';
    }

    /**
     * Boot the engine and its helpers.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->bootSharedHelpers();
        $this->bootEngineHelpers();
        $this->bootThemeHelpers();

        $this->booted = true;
    }

    /**
     * Boot shared helpers across all engines.
     */
    protected function bootSharedHelpers(): void
    {
        // Shared helpers logic or file include
    }

    /**
     * Boot engine-specific helpers (implemented by child classes).
     */
    protected function bootEngineHelpers(): void
    {
        // Default empty, override in child classes if needed
    }

    /**
     * Boot theme-specific helpers (e.g. from the theme directory).
     */
    protected function bootThemeHelpers(): void
    {
        $helpersPath = $this->theme->getPath() . '/src/helpers.php';
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }
}
