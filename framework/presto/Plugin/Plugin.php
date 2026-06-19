<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

use PrestoWorld\Contracts\Plugin\PluginInterface;
use Witals\Framework\Application;

abstract class Plugin implements PluginInterface
{
    protected bool $registered = false;
    protected bool $booted = false;

    public function __construct(
        protected Application $app,
        protected PluginManifest $manifest,
    ) {}

    public function getName(): string
    {
        return $this->manifest->name();
    }

    public function getTitle(): string
    {
        return $this->manifest->title();
    }

    public function getVersion(): string
    {
        return $this->manifest->version();
    }

    public function getDescription(): string
    {
        return $this->manifest->description();
    }

    public function getNamespace(): string
    {
        return $this->manifest->namespace();
    }

    public function getPath(): string
    {
        return $this->manifest->path();
    }

    public function getPriority(): int
    {
        return $this->manifest->priority();
    }

    public function isEnabled(): bool
    {
        return $this->manifest->enabled();
    }

    public function getDependencies(): array
    {
        return $this->manifest->dependencies();
    }

    public function getProvides(): array
    {
        return $this->manifest->provides();
    }

    public function getDeclaredHooks(): array
    {
        return $this->manifest->hooks();
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        foreach ($this->manifest->providers() as $provider) {
            if (is_string($provider) && class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;
    }

    public function activate(): void {}

    public function deactivate(): void {}

    public function uninstall(): void {}
}
