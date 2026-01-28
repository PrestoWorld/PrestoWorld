<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;
use App\Http\Routing\WordPressDispatcher;

class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Router::class, function ($app) {
            return new Router($app);
        });
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // Set the smart fallback to WordPress if the bridge is enabled
        $wpDispatcherClass = \Prestoworld\Bridge\WordPress\Routing\WordPressDispatcher::class;
        if ($this->app->has($wpDispatcherClass)) {
            $router->setWordPressFallback(function ($request) use ($wpDispatcherClass) {
                return $this->app->make($wpDispatcherClass)->dispatch($request);
            });
        }

        // Load modern routes
        $this->loadRoutes($router);
    }

    protected function loadRoutes(Router $router): void
    {
        $routesFile = $this->app->basePath('routes/web.php');
        if (file_exists($routesFile)) {
            // Make $router available in the routes file
            $router = $router; 
            require $routesFile;
        }
    }
}
