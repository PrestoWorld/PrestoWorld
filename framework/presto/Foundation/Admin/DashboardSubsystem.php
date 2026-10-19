<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Admin;

use PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenRegistryInterface;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository;
use Witals\Framework\Application;

/**
 * Registers a Seller Center subsystem ("phân hệ") into the dashboard menu
 * context. Modules call register() from their boot() phase; each subsystem
 * becomes its own menu group (tagged with a `subLabel` attribute) inside the
 * given section, and the Dashboard SPA renders the catalog from the
 * serialized `subsystems` state built by SpaController.
 */
final class DashboardSubsystem
{
    /** @deprecated Legacy container group; each subsystem is now its own group. */
    public const GROUP_ID = 'dashboard-subsystems';
    public const GROUP_LABEL = 'Danh Mục Phân Hệ';
    public const GROUP_PRIORITY = 0;

    public static function register(
        Application $app,
        string $id,
        string $label,
        string $subLabel,
        string $icon,
        int $priority,
        string $source = 'module',
        string $section = 'overview',
    ): void {
        if (!$app->has(MenuContextRepository::class)) {
            return;
        }

        $menu = $app->make(MenuContextRepository::class);
        if (!$menu instanceof MenuContextRepository) {
            return;
        }

        // Each subsystem is a standalone group inside its section. The
        // `subLabel` attribute marks it for buildSubsystemNav(); `section`
        // places it in the right sidebar category.
        $menu->registerGroup(
            $id,
            $label,
            icon: $icon,
            priority: $priority,
            attributes: [
                'subLabel' => $subLabel,
                'section' => $section,
                'source' => $source,
            ],
        );

        self::registerScreen($app, $id, $label, $icon, $priority, $source);
    }

    private static function registerScreen(
        Application $app,
        string $id,
        string $label,
        string $icon,
        int $priority,
        string $source,
    ): void {
        if (
            !$app->has(DashboardScreenRegistryInterface::class)
            || !class_exists(\PrestoWorld\Admin\Screen\Screen::class)
        ) {
            return;
        }

        $registry = $app->make(DashboardScreenRegistryInterface::class);
        if (!$registry instanceof DashboardScreenRegistryInterface) {
            return;
        }

        if ($registry->getScreen($id) !== null) {
            return;
        }

        $registry->registerScreen(new \PrestoWorld\Admin\Screen\Screen(
            id: $id,
            title: $label,
            icon: $icon,
            position: $priority,
            component: null,
            source: $source,
        ));
    }
}
