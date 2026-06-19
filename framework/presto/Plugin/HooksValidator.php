<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

use PrestoWorld\Contracts\Plugin\HooksRegistryInterface;

class HooksValidator
{
    private array $declaredByPlugin = [];
    private array $usedByPlugin = [];

    public function __construct(
        private HooksRegistryInterface $registry,
    ) {}

    public function registerPluginHooks(string $pluginName, array $declared, array $used): array
    {
        $errors = [];

        foreach ($declared as $hook) {
            if (isset($this->declaredByPlugin[$hook]) && $this->declaredByPlugin[$hook] !== $pluginName) {
                $errors[] = "Hook '{$hook}' is already declared by plugin '{$this->declaredByPlugin[$hook]}'. Plugin '{$pluginName}' cannot redeclare it";
                continue;
            }

            $this->declaredByPlugin[$hook] = $pluginName;
            $this->registry->declareHook($pluginName, $hook, 'action');
        }

        foreach ($used as $hook) {
            if (!isset($this->declaredByPlugin[$hook])) {
                $errors[] = "Plugin '{$pluginName}' uses undeclared hook '{$hook}'. No plugin has declared this hook";
                continue;
            }

            $this->usedByPlugin[$pluginName][] = $hook;
            $this->registry->registerHookUse($pluginName, $hook);
        }

        return $errors;
    }

    public function validateHookUsage(string $pluginName, string $hook, string $type): bool
    {
        $hookDef = $this->declaredByPlugin[$hook] ?? null;

        if ($hookDef === null) {
            return false;
        }

        $usedHooks = $this->usedByPlugin[$pluginName] ?? [];

        if (!in_array($hook, $usedHooks, true)) {
            return false;
        }

        return true;
    }

    public function getDeclaredByPlugin(): array
    {
        return $this->declaredByPlugin;
    }

    public function getUndefinedHooks(): array
    {
        $undefined = [];

        foreach ($this->usedByPlugin as $plugin => $hooks) {
            foreach ($hooks as $hook) {
                if (!isset($this->declaredByPlugin[$hook])) {
                    $undefined[] = [
                        'plugin' => $plugin,
                        'hook' => $hook,
                    ];
                }
            }
        }

        return $undefined;
    }

    public function getUnusedDeclaredHooks(): array
    {
        $allUsed = [];

        foreach ($this->usedByPlugin as $hooks) {
            $allUsed = array_merge($allUsed, $hooks);
        }

        $unused = [];

        foreach ($this->declaredByPlugin as $hook => $plugin) {
            if (!in_array($hook, $allUsed, true)) {
                $unused[] = [
                    'hook' => $hook,
                    'declared_by' => $plugin,
                ];
            }
        }

        return $unused;
    }

    public function reset(): void
    {
        $this->declaredByPlugin = [];
        $this->usedByPlugin = [];
    }
}
