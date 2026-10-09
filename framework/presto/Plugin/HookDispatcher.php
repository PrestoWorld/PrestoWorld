<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

use PrestoWorld\Core\Legacy\LegacyRegistry;
use PrestoWorld\Core\Legacy\LegacyState;
use Witals\Framework\Contracts\ResettableInterface;
use Witals\Framework\Module\Contracts\HookInterface;

/**
 * Hook dispatcher — plugin-side adapter (spec 06 §6.2.1 + §6.5).
 *
 * Bridge: mọi hook mà plugin đăng ký (addAction/addFilter) và mọi hook được
 * trigger (doAction/applyFilters) đều route về canonical store LegacyState
 * (LegacyRegistry/LegacyInvoker) — CÙNG store với shim WP global
 * (add_action/do_action trong wp-compatibility.php). Nhờ đó:
 *   - Plugin hook 'admin.sidebar.menu' đăng ký qua đây sẽ được do_action()
 *     ở bất kỳ đâu (shim / LegacyHook / theme) kích hoạt.
 *   - Lazy loading vẫn hoạt động: plugin chỉ được nạp khi hook của nó chạm
 *     tới (ensureLazyLoaded chạy trước trigger).
 *   - compiledMap chỉ dùng để fast-path hasAction/hasFilter (O(1)), KHÔNG
 *     drop hook ở addAction/addFilter như trước (tránh mất hook WP-core
 *     không nằm trong manifest).
 */
class HookDispatcher implements HookInterface, ResettableInterface
{
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

    public function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        LegacyState::registry()->recordHook($hook, $callback, $priority, $acceptedArgs, LegacyRegistry::HOOK_ACTION);
    }

    public function doAction(string $hook, mixed ...$args): void
    {
        $this->ensureLazyLoaded($hook);

        LegacyState::invoker()->trigger($hook, array_values($args), LegacyRegistry::HOOK_ACTION);
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        LegacyState::registry()->recordHook($hook, $callback, $priority, $acceptedArgs, LegacyRegistry::HOOK_FILTER);
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        $this->ensureLazyLoaded($hook);

        return LegacyState::invoker()->trigger(
            $hook,
            array_values(array_merge([$value], $args)),
            LegacyRegistry::HOOK_FILTER,
        );
    }

    public function hasAction(string $hook): bool
    {
        return LegacyState::registry()->has($hook, null, LegacyRegistry::HOOK_ACTION) !== false
            || isset($this->compiledMap['actions'][$hook]);
    }

    public function hasFilter(string $hook): bool
    {
        return LegacyState::registry()->has($hook, null, LegacyRegistry::HOOK_FILTER) !== false
            || isset($this->compiledMap['filters'][$hook]);
    }

    public function removeAction(string $hook, callable $callback, int $priority = 10): void
    {
        LegacyState::registry()->removeHook($hook, $callback, $priority, LegacyRegistry::HOOK_ACTION);
    }

    public function removeFilter(string $hook, callable $callback, int $priority = 10): void
    {
        LegacyState::registry()->removeHook($hook, $callback, $priority, LegacyRegistry::HOOK_FILTER);
    }

    public function compileMap(): array
    {
        return [
            'actions' => [],
            'filters' => [],
        ];
    }

    public function reset(): void
    {
        // Canonical store được reset bởi LegacyState::reset() trong lifecycle
        // (spec 10 §10.7.2). Loader giữ lại vì chúng idempotent và tham chiếu
        // PluginManager — xoá sẽ phá lazy-load trên worker sau request đầu tiên.
        $this->compiledMap = null;
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
}