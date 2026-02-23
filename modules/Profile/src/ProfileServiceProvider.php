<?php

declare(strict_types=1);

namespace Modules\Profile;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class ProfileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\ProfileController::class, fn($app) => new Controllers\ProfileController($app));
        $this->singleton(Controllers\ProfileAdminController::class, fn($app) => new Controllers\ProfileAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- API ---
        $router->get('/api/profile',              [Controllers\ProfileController::class, 'show']);
        $router->put('/api/profile',              [Controllers\ProfileController::class, 'update']);
        $router->post('/api/profile/avatar',      [Controllers\ProfileController::class, 'updateAvatar']);
        $router->get('/api/profiles/{userId}',    [Controllers\ProfileController::class, 'showUser']);

        // --- Admin UI ---
        $router->get('/dashboard/profile',            [Controllers\ProfileAdminController::class, 'show']);
        $router->post('/dashboard/profile',           [Controllers\ProfileAdminController::class, 'update']);
    }
}
