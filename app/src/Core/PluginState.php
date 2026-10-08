<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * PluginState — is_active_plugins/mở-tắt plugin (spec 10 §10.4).
 */
final class PluginState
{
    private function __construct()
    {
    }

    public static function isActive(string $plugin): bool
    {
        return in_array($plugin, (array) OptionRepository::get('active_plugins', []), true);
    }

    public static function isInactive(string $plugin): bool
    {
        return !self::isActive($plugin);
    }

    public static function activate(string $plugin): void
    {
        $active = (array) OptionRepository::get('active_plugins', []);
        if (!in_array($plugin, $active, true)) {
            $active[] = $plugin;
            OptionRepository::update('active_plugins', array_values($active));
        }
    }

    public static function deactivate(string $plugin): void
    {
        $active = (array) OptionRepository::get('active_plugins', []);
        $active = array_values(array_filter($active, static fn (mixed $p): bool => $p !== $plugin));
        OptionRepository::update('active_plugins', $active);
    }
}