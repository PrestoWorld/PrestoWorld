<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

use PrestoWorld\Contracts\Plugin\HooksRegistryInterface;

class HooksRegistry implements HooksRegistryInterface
{
    private array $declared = [];
    private array $usedByPlugin = [];

    public function declareHook(string $pluginName, string $hook, string $type): void
    {
        $this->declared[$hook] = [
            'plugin' => $pluginName,
            'type' => $type,
        ];
    }

    public function registerHookUse(string $pluginName, string $hook): void
    {
        $this->usedByPlugin[$pluginName][] = $hook;
    }

    public function isHookDeclared(string $hook): bool
    {
        return isset($this->declared[$hook]);
    }

    public function isHookAllowed(string $pluginName, string $hook): bool
    {
        if (!isset($this->declared[$hook])) {
            return false;
        }

        $usedHooks = $this->usedByPlugin[$pluginName] ?? [];

        return in_array($hook, $usedHooks, true);
    }

    public function getDeclaredHooks(): array
    {
        return $this->declared;
    }

    public function getPluginHooks(string $pluginName): array
    {
        return [
            'declared' => array_keys(
                array_filter($this->declared, fn($d) => $d['plugin'] === $pluginName),
            ),
            'used' => $this->usedByPlugin[$pluginName] ?? [],
        ];
    }

    public function getUnusedDeclaredHooks(): array
    {
        $allUsed = [];

        foreach ($this->usedByPlugin as $hooks) {
            $allUsed = array_merge($allUsed, $hooks);
        }

        $unused = [];

        foreach ($this->declared as $hook => $meta) {
            if (!in_array($hook, $allUsed, true)) {
                $unused[] = [
                    'hook' => $hook,
                    'declared_by' => $meta['plugin'],
                ];
            }
        }

        return $unused;
    }

    public function getUndefinedHooks(): array
    {
        $undefined = [];

        foreach ($this->usedByPlugin as $plugin => $hooks) {
            foreach ($hooks as $hook) {
                if (!isset($this->declared[$hook])) {
                    $undefined[] = [
                        'plugin' => $plugin,
                        'hook' => $hook,
                    ];
                }
            }
        }

        return $undefined;
    }
}
