<?php

declare(strict_types=1);

namespace PrestoWorld\Admin\Drivers;

use PrestoWorld\Admin\Contracts\DashboardDriver;
use App\Http\Routing\Router;
use Witals\Framework\Http\Response;

class PrestoDashboardDriver implements DashboardDriver
{
    public function getName(): string
    {
        return 'presto';
    }

    public function isSupported(): bool
    {
        return true; // Always supported as it's built-in
    }

    public function getRoutePrefix(): string
    {
        return '/dashboard';
    }

    public function registerRoutes(Router $router): void
    {
        $prefix = $this->getRoutePrefix();

        $router->get($prefix, function () {
            // Immutability: Admin context
            $GLOBALS['__presto_admin_context'] = ['driver' => 'presto'];
            
            return Response::html('<h1>Welcome to PrestoWorld Native Dashboard</h1><p>This is the built-in, immutable admin panel.</p>');
        });

        $router->get($prefix . '/settings', function () {
            return Response::html('<h1>Presto Settings</h1>');
        });
    }
}
