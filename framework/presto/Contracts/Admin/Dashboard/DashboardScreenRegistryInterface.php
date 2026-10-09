<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Dashboard;

use PrestoWorld\Contracts\Admin\Screen\ScreenInterface;

interface DashboardScreenRegistryInterface
{
    public function registerScreen(ScreenInterface $screen): void;

    public function removeScreen(string $id): void;

    /**
     * @return ScreenInterface[]
     */
    public function getScreens(): array;

    public function getScreen(string $id): ?ScreenInterface;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array;

    public function addProvider(DashboardScreenProviderInterface $provider): void;

    /**
     * @return DashboardScreenProviderInterface[]
     */
    public function getProviders(): array;
}