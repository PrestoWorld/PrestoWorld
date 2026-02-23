<?php

declare(strict_types=1);

namespace Modules\Webhooks;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class WebhooksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\WebhookController::class, fn($app) => new Controllers\WebhookController($app));
        $this->singleton(Controllers\WebhookAdminController::class, fn($app) => new Controllers\WebhookAdminController($app));
        $this->singleton(Services\WebhookDispatcher::class, fn($app) => new Services\WebhookDispatcher($app));
        $this->app->instance('webhooks', $this->app->make(Services\WebhookDispatcher::class));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- API ---
        $router->get('/api/webhooks',                        [Controllers\WebhookController::class, 'index']);
        $router->get('/api/webhooks/{id}',                   [Controllers\WebhookController::class, 'show']);
        $router->post('/api/webhooks',                       [Controllers\WebhookController::class, 'store']);
        $router->put('/api/webhooks/{id}',                   [Controllers\WebhookController::class, 'update']);
        $router->delete('/api/webhooks/{id}',                [Controllers\WebhookController::class, 'destroy']);
        $router->get('/api/webhooks/{id}/deliveries',        [Controllers\WebhookController::class, 'deliveries']);

        // --- Admin UI ---
        $router->get('/dashboard/webhooks',                      [Controllers\WebhookAdminController::class, 'index']);
        $router->get('/dashboard/webhooks/create',               [Controllers\WebhookAdminController::class, 'create']);
        $router->post('/dashboard/webhooks/create',              [Controllers\WebhookAdminController::class, 'store']);
        $router->get('/dashboard/webhooks/{id}/edit',            [Controllers\WebhookAdminController::class, 'edit']);
        $router->put('/dashboard/webhooks/{id}/edit',            [Controllers\WebhookAdminController::class, 'update']);
        $router->get('/dashboard/webhooks/{id}/deliveries',      [Controllers\WebhookAdminController::class, 'deliveries']);
    }
}
