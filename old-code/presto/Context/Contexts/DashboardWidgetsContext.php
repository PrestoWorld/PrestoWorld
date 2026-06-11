<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contexts;

use PrestoWorld\Context\AbstractContext;

/**
 * Dashboard Widgets Context
 *
 * Holds all stat cards, charts, and info widgets rendered on the admin dashboard.
 * Modules register WidgetContexts here.
 */
class DashboardWidgetsContext extends AbstractContext
{
    public function getName(): string  { return 'dashboard.widgets'; }
    public function getLabel(): string { return 'Dashboard Widgets'; }
}
