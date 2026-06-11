<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contexts;

use PrestoWorld\Context\AbstractContext;

/**
 * Dashboard Menu Context
 *
 * Holds sidebar navigation items for the admin dashboard.
 * Modules register MenuItemContexts here (e.g. Licenses, Orders, Customers…).
 */
class DashboardMenuContext extends AbstractContext
{
    public function getName(): string  { return 'dashboard.menu'; }
    public function getLabel(): string { return 'Dashboard Sidebar Menu'; }
}
