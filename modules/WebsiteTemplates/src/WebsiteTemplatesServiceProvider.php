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
        error_log("WebsiteTemplates: Booting...");
        $router = $this->app->make(Router::class);
        $locales = config('app.locales', ['en']);

        foreach ($locales as $locale) {
            $path = __('routes.web-templates', [], $locale);
            // If translation is missing OR it returned the key, use a default fallback
            if ($path === 'routes.web-templates' || empty($path)) {
                $path = 'web-templates';
            }
            
            error_log("WebsiteTemplates: Registering path '{$path}' for locale '{$locale}'");
            
            // Register the clean path. LocalizedRouter will match it after stripping the /vi, /ja prefix.
            $router->get($path, [TemplateController::class, 'index']);
            $router->get($path . '/{slug}', [TemplateController::class, 'show']);
        }
    }
}
