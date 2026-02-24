<?php

declare(strict_types=1);

namespace Modules\WebsiteTemplates;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;
use Modules\WebsiteTemplates\Controllers\TemplateController;

class WebsiteTemplatesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // Public Routes
        $router->get('/web-mau', [TemplateController::class, 'index']);
        $router->get('/web-mau/{slug}', [TemplateController::class, 'show']);
    }
}
