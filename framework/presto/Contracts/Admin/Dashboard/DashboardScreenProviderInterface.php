<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Dashboard;

interface DashboardScreenProviderInterface
{
    public function getIdentifier(): string;

    /**
     * @return \PrestoWorld\Contracts\Admin\Screen\ScreenInterface[]
     */
    public function getScreens(): array;

    public function getPriority(): int;
}