<?php

declare(strict_types=1);

namespace PrestoWorld\Theme;

use App\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(ThemeManager::class, function ($app) {
            $manager = new ThemeManager($app);
            
            // Register native themes path
            $manager->addDiscoveryPath($app->basePath('themes'));

            // Register WordPress themes path
            $manager->addDiscoveryPath($app->basePath('public/wp-content/themes'));
            
            return $manager;
        });
    }

    public function boot(): void
    {
        $manager = $this->app->make(ThemeManager::class);
        
        $manager->discover();
        
        // Set default theme from env or wp_options
        $themeActive = env('THEME_ACTIVE');
        $activeTheme = env('ACTIVE_THEME');
        
        // error_log("ENV CHECK - THEME_ACTIVE: '{$themeActive}', ACTIVE_THEME: '{$activeTheme}'");

        $defaultTheme = $themeActive ?: $activeTheme;
        if (!$defaultTheme && $this->app->has('wp.options')) {
            $defaultTheme = $this->app->make('wp.options')->get('template');
        }

        // Fallback to tucnguyen
        if (!$defaultTheme || $defaultTheme === 'default') {
            $defaultTheme = 'tucnguyen';
        }

        error_log("Selected active theme: " . $defaultTheme);
        $manager->setActiveTheme($defaultTheme);
        
        if ($manager->getActiveTheme()) {
             error_log("Activated theme: " . $manager->getActiveTheme()->getName() . " Type: " . $manager->getActiveTheme()->getType());
        } else {
             error_log("Failed to activate theme: " . $defaultTheme);
        }
    }
}
