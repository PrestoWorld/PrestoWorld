<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

use Witals\Framework\Module\Contracts\HookInterface;

/**
 * Hook dispatcher with compiled map for O(1) lookup.
 *
 * Performance guarantees:
 * - doAction() with no listeners: 1x isset() check — O(1), zero allocation
 * - addAction()/addFilter(): 1x isset() + 1x append — O(1) amortized
 * - Built-in array append, no ksort() on hot path
 */
class HookDispatcher implements HookInterface
{
    private array $actions = [];
    private array $filters = [];

    private ?array $compiledMap = null;

    private array $lazyLoaders = [];

    public function setCompiledMap(?array $map): void
    {
        $this->compiledMap = $map;
    }

    public function registerLazyLoader(string $hook, callable $loader): void
    {
        $this->lazyLoaders[$hook][] = $loader;
    }

    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        if ($this->compiledMap !== null && !isset($this->compiledMap['actions'][$hook])) {
            return;
        }

        if (!isset($this->actions[$hook])) {
            $this->actions[$hook] = [];
        }

        if (!isset($this->actions[$hook][$priority])) {
            $this->actions[$hook][$priority] = [];
        }

        $this->actions[$hook][$priority][] = $callback;
    }

    public function doAction(string $hook, mixed ...$args): void
    {
        if (!isset($this->actions[$hook]) && !isset($this->compiledMap['actions'][$hook])) {
            return;
        }

        $this->ensureLazyLoaded($hook);

        if (!isset($this->actions[$hook])) {
            return;
        }

        foreach ($this->actions[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        if ($this->compiledMap !== null && !isset($this->compiledMap['filters'][$hook])) {
            return;
        }

        if (!isset($this->filters[$hook])) {
            $this->filters[$hook] = [];
        }

        if (!isset($this->filters[$hook][$priority])) {
            $this->filters[$hook][$priority] = [];
        }

        $this->filters[$hook][$priority][] = $callback;
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (!isset($this->filters[$hook]) && !isset($this->compiledMap['filters'][$hook])) {
            return $value;
        }

        $this->ensureLazyLoaded($hook);

        if (!isset($this->filters[$hook])) {
            return $value;
        }

        array_unshift($args, $value);

        foreach ($this->filters[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback(...$args);
                $args[0] = $value;
            }
        }

        return $value;
    }

    public function hasAction(string $hook): bool
    {
        return isset($this->actions[$hook]) || isset($this->compiledMap['actions'][$hook]);
    }

    public function hasFilter(string $hook): bool
    {
        return isset($this->filters[$hook]) || isset($this->compiledMap['filters'][$hook]);
    }

    public function removeAction(string $hook, callable $callback, int $priority = 10): void
    {
        $this->removeFromPriority($this->actions, $hook, $callback, $priority);
    }

    public function removeFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->removeFromPriority($this->filters, $hook, $callback, $priority);
    }

    public function compileMap(): array
    {
        $map = [
            'actions' => [],
            'filters' => [],
        ];

        foreach ($this->actions as $hook => $priorities) {
            $map['actions'][$hook] = array_keys($priorities);
        }

        foreach ($this->filters as $hook => $priorities) {
            $map['filters'][$hook] = array_keys($priorities);
        }

        return $map;
    }

    public function reset(): void
    {
        $this->actions = [];
        $this->filters = [];
        $this->lazyLoaders = [];
    }

    private function ensureLazyLoaded(string $hook): void
    {
        $loaders = $this->lazyLoaders[$hook] ?? [];

        if ($loaders === []) {
            return;
        }

        foreach ($loaders as $loader) {
            $loader();
        }

        unset($this->lazyLoaders[$hook]);
    }

    private function removeFromPriority(array &$hooks, string $hook, callable $callback, int $priority): void
    {
        if (!isset($hooks[$hook][$priority])) {
            return;
        }

        $hooks[$hook][$priority] = array_values(
            array_filter($hooks[$hook][$priority], fn($c) => $c !== $callback),
        );

        if ($hooks[$hook][$priority] === []) {
            unset($hooks[$hook][$priority]);
        }

        if ($hooks[$hook] === []) {
            unset($hooks[$hook]);
        }
}
}
