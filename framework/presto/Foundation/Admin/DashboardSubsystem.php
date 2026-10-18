<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Admin;

use PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenRegistryInterface;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository;
use Witals\Framework\Application;

/**
 * Registers a Seller Center subsystem ("phân hệ") into the dashboard menu
 * context. Modules call register() from their boot() phase; the Dashboard
 * SPA renders the catalog from the serialized `subsystems` state built by
 * SpaController from the `dashboard-subsystems` menu group.
 */
final class DashboardSubsystem
{
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
    ): void {
        if (!$app->has(MenuContextRepository::class)) {
            return;
        }

        $menu = $app->make(MenuContextRepository::class);
        if (!$menu instanceof MenuContextRepository) {
            return;
        }

        $menu->registerGroup(
            self::GROUP_ID,
            self::GROUP_LABEL,
            icon: 'Layers',
            priority: self::GROUP_PRIORITY,
        );

        $menu->registerItem(
            self::GROUP_ID,
            $label,
            '#/' . $id,
            icon: $icon,
            attributes: ['subLabel' => $subLabel],
            priority: $priority,
            id: $id,
            screenId: $id,
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
            || !class_exists(\PrestoWorld\Modules\Admin\Screen\Screen::class)
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

        $registry->registerScreen(new \PrestoWorld\Modules\Admin\Screen\Screen(
            id: $id,
            title: $label,
            icon: $icon,
            position: $priority,
            component: null,
            source: $source,
        ));
    }
}
