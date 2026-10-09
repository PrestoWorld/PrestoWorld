<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * PluginActivator — activate/deactivate plugin + lifecycle hooks
 * (spec 10 §10.4.19 Group 18, 06 §6.5). Trạng thái active lưu qua PluginState.
 */
final class PluginActivator
{
    /** @var array<string, callable> */
    private static array $activationHooks = [];

    /** @var array<string, callable> */
    private static array $deactivationHooks = [];

    /** @var array<string, callable> */
    private static array $uninstallHooks = [];

    private function __construct()
    {
    }

    public static function activate(string $plugin, bool $silent = false): void
    {
        PluginState::activate($plugin);
        self::invoke(self::$activationHooks[self::key($plugin)] ?? null);
    }

    public static function deactivate(string $plugin, bool $silent = false): void
    {
        PluginState::deactivate($plugin);
        self::invoke(self::$deactivationHooks[self::key($plugin)] ?? null);
    }

    public static function uninstall(string $plugin): void
    {
        self::invoke(self::$uninstallHooks[self::key($plugin)] ?? null);
        unset(self::$uninstallHooks[self::key($plugin)]);
    }

    /**
     * @param string|array<int, string> $plugins
     */
    public static function deactivateMany(string|array $plugins, bool $silent = false): void
    {
        foreach ((array) $plugins as $plugin) {
            self::deactivate($plugin, $silent);
        }
    }

    public static function onActivation(string $file, callable $callback): void
    {
        self::$activationHooks[self::key($file)] = $callback;
    }

    public static function onDeactivation(string $file, callable $callback): void
    {
        self::$deactivationHooks[self::key($file)] = $callback;
    }

    public static function onUninstall(string $file, callable $callback): void
    {
        self::$uninstallHooks[self::key($file)] = $callback;
    }

    public static function reset(): void
    {
        self::$activationHooks = [];
        self::$deactivationHooks = [];
        self::$uninstallHooks = [];
    }

    private static function key(string $file): string
    {
        return basename(str_replace('\\', '/', $file));
    }

    private static function invoke(?callable $callback): void
    {
        if ($callback === null) {
            return;
        }

        try {
            $callback();
        } catch (\Throwable) {
            // Lifecycle callback lỗi không chặn thao tác plugin.
        }
    }
}
