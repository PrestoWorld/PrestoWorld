<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceProvider;
use Witals\Framework\Contracts\View\Factory as ViewFactory;
use Witals\Framework\View\Engines\StemplerEngine;
use Witals\Framework\View\Engines\PhpEngine;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register view services.
     */
    public function register(): void
    {
        // The Application class already initializes a ViewManager, 
        // but we can customize it here.
        
        $this->app->extend(ViewFactory::class, function (ViewFactory $view, $app) {
            $cachePath = $app->basePath('storage/framework/views');
            
            // Register Stempler Engine for .stempler.php and .dark.php files
            $stempler = new StemplerEngine($cachePath, [
                $app->basePath('resources/views'),
            ], ['.stempler.php', '.dark.php']);
            
            $view->registerEngine('stempler.php', $stempler);
            $view->registerEngine('dark.php', $stempler);
            
            // Add global views location
            $view->addLocation($app->basePath('resources/views'));

            // Initialize rendering state
            $app->instance('view.rendered', false);
            
            return $view;
        });
    }

    /**
     * Boot view services.
     */
    public function boot(): void
    {
        $app = $this->app;
        // Automatically add Active Theme's views to location list
        if ($app->has(\PrestoWorld\Theme\ThemeManager::class)) {
            $themeManager = $app->make(\PrestoWorld\Theme\ThemeManager::class);
            if ($theme = $themeManager->getActiveTheme()) {
                $view = $app->make(ViewFactory::class);
                $themeViews = $theme->getPath() . '/resources/views';
                
                // Prepend to prioritize theme over framework views
                $view->prependLocation($themeViews);

                // Update engines searching paths
                foreach ($view->getEngines() as $engine) {
                    if (method_exists($engine, 'prependPath')) {
                        $engine->prependPath($themeViews);
                    }
                }
            }
        }
    }
}
