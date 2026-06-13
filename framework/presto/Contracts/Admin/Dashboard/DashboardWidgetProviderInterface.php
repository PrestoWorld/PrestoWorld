<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Dashboard;

interface DashboardWidgetProviderInterface
{
    public function getIdentifier(): string;
    public function getWidgets(string $context = 'dashboard'): array;
    public function getPriority(): int;
}
