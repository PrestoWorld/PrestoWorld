<?php

declare(strict_types=1);

/*
 * WordPress compatibility shims — hooks group (spec 06 §6.2.1, 10 §10.4.11).
 *
 * Compile output mode-s giữ nguyên lời gọi \add_action(...); các hàm dưới đây
 * là đường runtime chính, delegate qua LegacyRegistry/LegacyInvoker (IoC).
 */

use PrestoWorld\Core\Legacy\LegacyRegistry;
use PrestoWorld\Core\Legacy\LegacyState;

if (!function_exists('add_action')) {
    /**
     * @param callable $callback
     */
    function add_action(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        LegacyState::registry()->recordHook($tag, $callback, $priority, $acceptedArgs, LegacyRegistry::HOOK_ACTION);
    }
}

if (!function_exists('add_filter')) {
    /**
     * @param callable $callback
     */
    function add_filter(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        LegacyState::registry()->recordHook($tag, $callback, $priority, $acceptedArgs, LegacyRegistry::HOOK_FILTER);
    }
}

if (!function_exists('do_action')) {
    function do_action(string $tag, mixed ...$args): void
    {
        LegacyState::invoker()->trigger($tag, $args, LegacyRegistry::HOOK_ACTION);
    }
}

if (!function_exists('do_action_ref_array')) {
    /**
     * @param array<int, mixed> $args
     */
    function do_action_ref_array(string $tag, array $args): void
    {
        LegacyState::invoker()->trigger($tag, $args, LegacyRegistry::HOOK_ACTION);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $tag, mixed $value, mixed ...$args): mixed
    {
        return LegacyState::invoker()->trigger($tag, array_merge([$value], $args), LegacyRegistry::HOOK_FILTER);
    }
}

if (!function_exists('apply_filters_ref_array')) {
    /**
     * @param array<int, mixed> $args
     */
    function apply_filters_ref_array(string $tag, array $args): mixed
    {
        return LegacyState::invoker()->trigger($tag, $args, LegacyRegistry::HOOK_FILTER);
    }
}

if (!function_exists('remove_action')) {
    /**
     * @param callable|null $callback
     */
    function remove_action(string $tag, ?callable $callback = null, ?int $priority = null): bool
    {
        return LegacyState::registry()->removeHook($tag, $callback, $priority, LegacyRegistry::HOOK_ACTION);
    }
}

if (!function_exists('remove_filter')) {
    /**
     * @param callable|null $callback
     */
    function remove_filter(string $tag, ?callable $callback = null, ?int $priority = null): bool
    {
        return LegacyState::registry()->removeHook($tag, $callback, $priority, LegacyRegistry::HOOK_FILTER);
    }
}

if (!function_exists('remove_all_actions')) {
    function remove_all_actions(string $tag, ?int $priority = null): bool
    {
        return LegacyState::registry()->removeAll($tag, $priority, LegacyRegistry::HOOK_ACTION);
    }
}

if (!function_exists('remove_all_filters')) {
    function remove_all_filters(string $tag, ?int $priority = null): bool
    {
        return LegacyState::registry()->removeAll($tag, $priority, LegacyRegistry::HOOK_FILTER);
    }
}

if (!function_exists('has_action')) {
    /**
     * @param callable|null $callback
     * @return int|false
     */
    function has_action(string $tag, ?callable $callback = null): int|false
    {
        return LegacyState::registry()->has($tag, $callback, LegacyRegistry::HOOK_ACTION);
    }
}

if (!function_exists('has_filter')) {
    /**
     * @param callable|null $callback
     * @return int|false
     */
    function has_filter(string $tag, ?callable $callback = null): int|false
    {
        return LegacyState::registry()->has($tag, $callback, LegacyRegistry::HOOK_FILTER);
    }
}

if (!function_exists('did_action')) {
    function did_action(string $tag): int
    {
        return LegacyState::invoker()->did($tag);
    }
}

if (!function_exists('current_action')) {
    function current_action(): ?string
    {
        return LegacyState::invoker()->current();
    }
}

if (!function_exists('current_filter')) {
    function current_filter(): ?string
    {
        return LegacyState::invoker()->current();
    }
}

if (!function_exists('doing_action')) {
    function doing_action(?string $tag = null): bool
    {
        $invoker = LegacyState::invoker();

        if ($tag === null) {
            return $invoker->current() !== null;
        }

        return $invoker->isDoing($tag);
    }
}

if (!function_exists('doing_filter')) {
    function doing_filter(?string $tag = null): bool
    {
        return doing_action($tag);
    }
}

if (!function_exists('did_filter')) {
    function did_filter(string $tag): int
    {
        return LegacyState::invoker()->did($tag);
    }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata(): void
    {
        \PrestoWorld\Core\Legacy\LegacyState::reset();
    }
}

if (!function_exists('wp_reset_query')) {
    function wp_reset_query(): void
    {
        \PrestoWorld\Core\Legacy\LegacyState::reset();
    }
}

if (!function_exists('get_current_screen')) {
    function get_current_screen(): ?\PrestoWorld\Core\ScreenRegistry
    {
        $screen = \PrestoWorld\Core\ScreenRegistry::current();
        return $screen;
    }
}

if (!function_exists('wp_die')) {
    function wp_die(string $message = '', string $title = '', array|int $args = []): never
    {
        $status = 500;
        if (is_int($args)) {
            $status = $args;
        } elseif (is_array($args) && isset($args['response']) && is_int($args['response'])) {
            $status = $args['response'];
        }

        throw new \PrestoWorld\Core\Legacy\LegacyTerminationException($message, $status);
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook(string $file, callable $callback): void
    {
        LegacyState::registry()->recordHook('presto:activate:' . md5($file), $callback, 10, 0, LegacyRegistry::HOOK_ACTION);
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook(string $file, callable $callback): void
    {
        LegacyState::registry()->recordHook('presto:deactivate:' . md5($file), $callback, 10, 0, LegacyRegistry::HOOK_ACTION);
    }
}

if (!function_exists('register_uninstall_hook')) {
    function register_uninstall_hook(string $file, callable $callback): void
    {
        LegacyState::registry()->recordHook('presto:uninstall:' . md5($file), $callback, 10, 0, LegacyRegistry::HOOK_ACTION);
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename(string $file): string
    {
        return \PrestoWorld\Core\PluginInfo::basename($file);
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url(string $file): string
    {
        return rtrim(\PrestoWorld\Core\PluginInfo::url($file), '/') . '/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path(string $file): string
    {
        return rtrim(\PrestoWorld\Core\PluginInfo::path($file), '/') . '/';
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url(string $path = '', string $plugin = ''): string
    {
        return \PrestoWorld\Core\Url::content($path) . '/' . ltrim($path, '/');
    }
}