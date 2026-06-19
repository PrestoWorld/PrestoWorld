<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Plugin;

interface PluginInterface
{
    public function getName(): string;

    public function getTitle(): string;

    public function getVersion(): string;

    public function getDescription(): string;

    public function getNamespace(): string;

    public function getPath(): string;

    public function getPriority(): int;

    public function isEnabled(): bool;

    public function getDependencies(): array;

    public function getProvides(): array;

    public function getDeclaredHooks(): array;

    public function register(): void;

    public function boot(): void;

    public function activate(): void;

    public function deactivate(): void;

    public function uninstall(): void;
}
