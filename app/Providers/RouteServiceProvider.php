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
            return new Router($app, $app->make(\Psr\Log\LoggerInterface::class));
        });
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        error_log("RouteServiceProvider: Booting and loading routes...");

        // Set the smart fallback to WordPress if the bridge is enabled
        error_log("RouteServiceProvider: Force setting WordPress fallback dispatcher.");
        $router->setWordPressFallback(function ($request) {
            $wpDispatcherClass = \Prestoworld\Bridge\WordPress\Routing\WordPressDispatcher::class;
            return $this->app->make($wpDispatcherClass)->dispatch($request);
        });

        // Load modern routes
        $this->loadRoutes($router);
    }

    protected function loadRoutes(Router $router): void
    {
        $routesFile = $this->app->basePath('routes/web.php');
        if (file_exists($routesFile)) {
            error_log("RouteServiceProvider: Loading routes from " . $routesFile);
            require $routesFile;
        }
    }
}
