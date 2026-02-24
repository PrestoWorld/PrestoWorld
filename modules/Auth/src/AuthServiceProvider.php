<?php

declare(strict_types=1);

namespace Modules\Auth;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;
use Modules\Auth\Controllers\AuthController;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the controller in the container
        $this->singleton(AuthController::class, function ($app) {
            return new AuthController($app);
        });
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // Standard Login
        $router->get('/login', [AuthController::class, 'showLogin']);
        $router->post('/login', [AuthController::class, 'handleLogin']);

        // Registration
        $router->get('/register', [AuthController::class, 'showRegister']);
        $router->post('/register', [AuthController::class, 'handleRegister']);

        // Logout
        $router->get('/logout', [AuthController::class, 'handleLogout']);
        $router->post('/logout', [AuthController::class, 'handleLogout']);
    }
}
