<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Admin;

use PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenProviderInterface;
use PrestoWorld\Contracts\Admin\Screen\ScreenInterface;
use PrestoWorld\Modules\Admin\Screen\Screen;

abstract class BaseDashboardScreenProvider implements DashboardScreenProviderInterface
{
    /**
     * @return array<string, array{
     *     id: string,
     *     title: string,
     *     component: string,
     *     icon?: string,
     *     position?: int,
     *     settings?: array
     * }>
     */
    abstract protected function getScreenDefinitions(): array;

    public function getIdentifier(): string
    {
        return static::class;
    }

    /**
     * @return ScreenInterface[]
     */
    public function getScreens(): array
    {
        $screens = [];
        $definitions = $this->getScreenDefinitions();

        foreach ($definitions as $id => $def) {
            $screens[$id] = new Screen(
                id: $id,
                title: $def['title'],
                icon: $def['icon'] ?? 'Circle',
                position: $def['position'] ?? 10,
                component: $def['component'] ?? null,
                source: 'module',
                settings: $def['settings'] ?? [],
            );
        }

        return $screens;
    }

    public function getPriority(): int
    {
        return 10;
    }
}