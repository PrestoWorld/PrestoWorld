<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Plugin;

interface HooksRegistryInterface
{
    public function declareHook(string $pluginName, string $hook, string $type): void;

    public function registerHookUse(string $pluginName, string $hook): void;

    public function isHookDeclared(string $hook): bool;

    public function isHookAllowed(string $pluginName, string $hook): bool;

    public function getDeclaredHooks(): array;

    public function getPluginHooks(string $pluginName): array;

    public function getUnusedDeclaredHooks(): array;

    public function getUndefinedHooks(): array;
}
