<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Dashboard;

interface DashboardWidgetRepository
{
    public function registerWidget(DashboardWidget $widget): void;
    public function removeWidget(string $id): void;
    public function getWidgets(): array;
    public function getWidgetsByColumn(int $column, string $context = 'dashboard'): array;
    public function getWidgetsGroupedByColumn(string $context = 'dashboard'): array;
    public function addProvider(DashboardWidgetProviderInterface $provider): void;
    public function getProviders(): array;
    public function toJson(string $context = 'dashboard'): string;
}
