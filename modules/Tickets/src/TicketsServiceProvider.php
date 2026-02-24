<?php

declare(strict_types=1);

namespace Modules\Tickets;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class TicketsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\TicketController::class, fn($app) => new Controllers\TicketController($app));
        $this->singleton(Controllers\TicketAdminController::class, fn($app) => new Controllers\TicketAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- Frontend / Portal Tickets ---
        $router->get('/portal/tickets',                 [Controllers\TicketController::class, 'index']);
        $router->get('/portal/tickets/create',          [Controllers\TicketController::class, 'create']);
        $router->post('/portal/tickets/create',         [Controllers\TicketController::class, 'store']);
        $router->get('/portal/tickets/{id}',            [Controllers\TicketController::class, 'show']);
        $router->post('/portal/tickets/{id}/reply',     [Controllers\TicketController::class, 'reply']);

        // --- Frontend Guest Tracking (Optional/Public) ---
        $router->get('/support/track',                  [Controllers\TicketController::class, 'track']);

        // --- Admin Dashboard (CRUD) ---
        $router->get('/dashboard/support/tickets',            [Controllers\TicketAdminController::class, 'index']);
        $router->get('/dashboard/support/tickets/{id}',       [Controllers\TicketAdminController::class, 'show']);
        $router->post('/dashboard/support/tickets/{id}/reply', [Controllers\TicketAdminController::class, 'reply']);
        $router->put('/dashboard/support/tickets/{id}/status', [Controllers\TicketAdminController::class, 'updateStatus']);
    }
}
