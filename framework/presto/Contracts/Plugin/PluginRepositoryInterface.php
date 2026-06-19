<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Plugin;

interface PluginRepositoryInterface
{
    public function getName(): string;

    public function getLabel(): string;

    public function setConfig(array $config): void;

    public function discover(): array;

    public function fetch(string $pluginName, string $version): ?string;

    public function hasUpdate(string $pluginName, string $currentVersion): ?string;

    public function getPluginInfo(string $pluginName): ?array;
}
