<?php

declare(strict_types=1);

namespace PrestoWorld\Admin;

class DashboardWidgetRepository
{
    /** @var DashboardWidget[] */
    protected array $widgets = [];

    public function addWidget(DashboardWidget $widget): void
    {
        $this->widgets[$widget->widgetId] = $widget;
    }

    /**
     * @return DashboardWidget[]
     */
    public function getWidgets(): array
    {
        $widgets = $this->widgets;
        usort($widgets, function($a, $b) {
            $priorityA = $this->getPriorityValue($a->priority);
            $priorityB = $this->getPriorityValue($b->priority);
            return $priorityA <=> $priorityB;
        });
        return $widgets;
    }

    protected function getPriorityValue(string $priority): int
    {
        return match ($priority) {
            'high' => 1,
            'core' => 5,
            'default' => 10,
            'low' => 20,
            default => 10,
        };
    }
}
