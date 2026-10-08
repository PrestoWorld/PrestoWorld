<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Legacy;

use Witals\Framework\Module\Contracts\HookInterface;

/**
 * HookInterface → LegacyRegistry/LegacyInvoker (spec 06 §6.2.1 + 10 §10.7).
 *
 * Witals helpers toàn cục (add_action/do_action/add_filter/apply_filters)
 * gọi qua container HookInterface — adapter này route về LegacyState để shim
 * nội tại (did_action/current_filter/remove_all_actions...) thấy cùng state,
 * phân biệt action/filter tường minh và tôn trọng accepted_args/priority.
 */
final class LegacyHook implements HookInterface
{
    public function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        LegacyState::registry()->recordHook($hook, $callback, $priority, $acceptedArgs, LegacyRegistry::HOOK_ACTION);
    }

    public function doAction(string $hook, mixed ...$args): void
    {
        LegacyState::invoker()->trigger($hook, array_values($args), LegacyRegistry::HOOK_ACTION);
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        LegacyState::registry()->recordHook($hook, $callback, $priority, $acceptedArgs, LegacyRegistry::HOOK_FILTER);
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return LegacyState::invoker()->trigger(
            $hook,
            array_values(array_merge([$value], $args)),
            LegacyRegistry::HOOK_FILTER,
        );
    }

    public function removeAction(string $hook, callable $callback, int $priority = 10): void
    {
        LegacyState::registry()->removeHook($hook, $callback, $priority, LegacyRegistry::HOOK_ACTION);
    }

    public function removeFilter(string $hook, callable $callback, int $priority = 10): void
    {
        LegacyState::registry()->removeHook($hook, $callback, $priority, LegacyRegistry::HOOK_FILTER);
    }

    public function hasAction(string $hook): bool
    {
        return LegacyState::registry()->has($hook, null, LegacyRegistry::HOOK_ACTION) !== false;
    }

    public function hasFilter(string $hook): bool
    {
        return LegacyState::registry()->has($hook, null, LegacyRegistry::HOOK_FILTER) !== false;
    }
}