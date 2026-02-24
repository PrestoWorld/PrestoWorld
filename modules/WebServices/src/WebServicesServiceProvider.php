<?php

declare(strict_types=1);

namespace Modules\WebServices;

use App\Support\ServiceProvider as BaseServiceProvider;
use App\Http\Routing\Router;

class WebServicesServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\WebServiceController::class, fn($app) => new Controllers\WebServiceController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- API ---
        $router->get('/api/web-services',           [Controllers\WebServiceController::class, 'index']);
        $router->get('/api/web-services/seed',      [Controllers\WebServiceController::class, 'seed']);
        $router->get('/api/web-services/{slug}',    [Controllers\WebServiceController::class, 'show']);
        $router->post('/api/web-services/request',  [Controllers\WebServiceController::class, 'request']);

        // --- Frontend ---
        $router->get('/services',                   [Controllers\WebServiceController::class, 'catalog']);
    }
}
