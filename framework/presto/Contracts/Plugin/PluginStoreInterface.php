<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Plugin;

interface PluginStoreInterface
{
    public function isInstalled(string $pluginName): bool;

    public function getInstalledVersion(string $pluginName): ?string;

    public function markInstalled(string $pluginName, string $version, array $metadata): void;

    public function markUninstalled(string $pluginName): void;

    public function setEnabled(string $pluginName, bool $enabled): void;

    public function isEnabled(string $pluginName): bool;

    public function getInstalledPlugins(): array;

    public function getSchemaHash(string $pluginName): ?string;

    public function setSchemaHash(string $pluginName, string $hash): void;
}
