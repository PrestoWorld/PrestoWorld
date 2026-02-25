<?php

declare(strict_types=1);

namespace Modules\Blog;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Controllers\BlogController::class);
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- API ---
        $router->get('/api/blog/posts',           [Controllers\BlogController::class, 'apiIndex']);
        $router->get('/api/blog/posts/{slug}',    [Controllers\BlogController::class, 'apiShow']);
        $router->get('/api/blog/seed',            [Controllers\BlogController::class, 'seed']);

        // --- Frontend ---
        $router->get('/blog',                     [Controllers\BlogController::class, 'index']);
        $router->get('/blog/category/{slug}',     [Controllers\BlogController::class, 'category']);
        $router->get('/blog/tag/{slug}',          [Controllers\BlogController::class, 'tag']);
        $router->get('/blog/{slug}',              [Controllers\BlogController::class, 'show']);
    }
}
