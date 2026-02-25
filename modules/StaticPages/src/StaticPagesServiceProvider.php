<?php

declare(strict_types=1);

namespace Modules\StaticPages;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;
use Modules\StaticPages\Controllers\PageController;

class StaticPagesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // Register pages
        $pages = ['about', 'contact', 'privacy-policy'];

        foreach ($pages as $p) {
            $router->get('/' . $p, function($request) use ($p) {
                return $this->app->make(PageController::class)->show($request, $p);
            });
        }
    }
}
