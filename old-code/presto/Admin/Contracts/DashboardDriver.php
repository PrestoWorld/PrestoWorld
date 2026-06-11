<?php

declare(strict_types=1);

namespace PrestoWorld\Admin\Contracts;

use App\Http\Routing\Router;

interface DashboardDriver
{
    /**
     * Get the unique identifier for the dashboard driver.
     */
    public function getName(): string;

    /**
     * Check if this driver should be potentially active.
     * This allows drivers to self-disable based on environment.
     */
    public function isSupported(): bool;

    /**
     * Get the URL prefix for this dashboard (e.g. '/admin' or '/wp-admin').
     */
    public function getRoutePrefix(): string;

    /**
     * Register routes for this dashboard.
     */
    public function registerRoutes(Router $router): void;
}
