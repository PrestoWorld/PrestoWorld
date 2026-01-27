<?php

declare(strict_types=1);

namespace App\Foundation\Theme;

use App\Foundation\Application;
use App\Foundation\Theme\Contracts\ThemeInterface;

class Theme implements ThemeInterface
{
    protected Application $app;
    protected string $path;
    protected array $metadata;
    protected bool $booted = false;

    public function __construct(Application $app, string $path, array $metadata)
    {
        $this->app = $app;
        $this->path = $path;
        $this->metadata = $metadata;
    }

    public function getName(): string
    {
        return $this->metadata['name'] ?? 'unknown';
    }

    public function getTitle(): string
    {
        return $this->metadata['title'] ?? $this->getName();
    }

    public function getVersion(): string
    {
        return $this->metadata['version'] ?? '1.0.0';
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return $this->metadata['type'] ?? 'native';
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Logic to load theme service providers or helpers
        $helpersPath = $this->path . '/src/helpers.php';
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }

        $this->booted = true;
    }

    public function isActive(): bool
    {
        return $this->app->make(ThemeManager::class)->getActiveTheme() === $this;
    }
}
