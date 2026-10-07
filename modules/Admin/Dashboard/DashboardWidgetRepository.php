<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\Dashboard;

use PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetRepository as DashboardWidgetRepositoryContract;
use PrestoWorld\Contracts\Admin\Dashboard\DashboardWidget as DashboardWidgetContract;
use PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetProviderInterface;

class DashboardWidgetRepository implements DashboardWidgetRepositoryContract
{
    protected array $widgets = [];
    protected array $providers = [];
    protected bool $providersLoaded = false;

    public function registerWidget(DashboardWidgetContract $widget): void
    {
        $this->widgets[$widget->getId()] = $widget;
    }

    public function removeWidget(string $id): void
    {
        unset($this->widgets[$id]);
    }

    public function getWidgets(): array
    {
        $this->loadProviders();
        return $this->sortWidgets($this->widgets);
    }

    public function getWidgetsByColumn(int $column, string $context = 'dashboard'): array
    {
        $all = $this->getWidgets();
        return array_values(array_filter(
            $all,
            fn(DashboardWidgetContract $w) => $w->getColumn() === $column && $w->getContext() === $context && $w->isVisible(),
        ));
    }

    public function getWidgetsGroupedByColumn(string $context = 'dashboard'): array
    {
        $columns = [];

        foreach ($this->getWidgets() as $widget) {
            if ($widget->getContext() !== $context || !$widget->isVisible()) {
                continue;
            }
            $col = $widget->getColumn();
            if (!isset($columns[$col])) {
                $columns[$col] = [];
            }
            $columns[$col][] = $widget;
        }

        ksort($columns);
        return $columns;
    }

    public function addProvider(DashboardWidgetProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        $this->providersLoaded = false;
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    public function toJson(string $context = 'dashboard'): string
    {
        $data = [];
        foreach ($this->getWidgetsGroupedByColumn($context) as $column => $widgets) {
            $data[$column] = array_map(
                fn(DashboardWidgetContract $w) => $w->toArray(),
                $widgets,
            );
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function loadProviders(): void
    {
        if ($this->providersLoaded) {
            return;
        }

        $sorted = $this->sortProviders($this->providers);

        foreach ($sorted as $provider) {
            $items = $provider->getWidgets();
            foreach ($items as $widget) {
                if ($widget instanceof DashboardWidgetContract) {
                    $this->widgets[$widget->getId()] = $widget;
                }
            }
        }

        $this->providersLoaded = true;
    }

    protected function sortWidgets(array $widgets): array
    {
        usort($widgets, fn(DashboardWidgetContract $a, DashboardWidgetContract $b) => $a->getPriority() <=> $b->getPriority());
        return $widgets;
    }

    protected function sortProviders(array $providers): array
    {
        usort($providers, fn(DashboardWidgetProviderInterface $a, DashboardWidgetProviderInterface $b) => $a->getPriority() <=> $b->getPriority());
        return $providers;
    }
}
