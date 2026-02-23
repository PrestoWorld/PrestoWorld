<?php

declare(strict_types=1);

namespace Modules\Memberships;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class MembershipsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\MembershipController::class, fn($app) => new Controllers\MembershipController($app));
        $this->singleton(Controllers\MembershipAdminController::class, fn($app) => new Controllers\MembershipAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- API ---
        $router->get('/api/membership-plans',           [Controllers\MembershipController::class, 'plans']);
        $router->post('/api/membership-plans',          [Controllers\MembershipController::class, 'storePlan']);
        $router->put('/api/membership-plans/{id}',      [Controllers\MembershipController::class, 'updatePlan']);
        $router->delete('/api/membership-plans/{id}',   [Controllers\MembershipController::class, 'destroyPlan']);
        $router->get('/api/memberships',                [Controllers\MembershipController::class, 'index']);
        $router->get('/api/memberships/{id}',           [Controllers\MembershipController::class, 'show']);
        $router->post('/api/memberships',               [Controllers\MembershipController::class, 'store']);
        $router->put('/api/memberships/{id}',           [Controllers\MembershipController::class, 'update']);
        $router->delete('/api/memberships/{id}',        [Controllers\MembershipController::class, 'destroy']);

        // --- Admin UI: Plans ---
        $router->get('/dashboard/membership-plans',                  [Controllers\MembershipAdminController::class, 'plans']);
        $router->get('/dashboard/membership-plans/create',           [Controllers\MembershipAdminController::class, 'createPlan']);
        $router->post('/dashboard/membership-plans/create',          [Controllers\MembershipAdminController::class, 'storePlan']);

        // --- Admin UI: Subscriptions ---
        $router->get('/dashboard/memberships',                       [Controllers\MembershipAdminController::class, 'index']);
        $router->get('/dashboard/memberships/create',                [Controllers\MembershipAdminController::class, 'create']);
        $router->post('/dashboard/memberships/create',               [Controllers\MembershipAdminController::class, 'store']);
        $router->get('/dashboard/memberships/{id}/edit',             [Controllers\MembershipAdminController::class, 'edit']);
        $router->put('/dashboard/memberships/{id}/edit',             [Controllers\MembershipAdminController::class, 'update']);
    }
}
