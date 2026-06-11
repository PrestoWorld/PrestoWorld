<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceProvider;
use Witals\Framework\Contracts\View\Factory as ViewFactory;
use Witals\Framework\View\Engines\StemplerEngine;
use Witals\Framework\View\Engines\PhpEngine;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(ViewFactory::class, function (ViewFactory $view, $app) {
            $cachePath = $app->basePath('storage/framework/views');

            $stempler = new StemplerEngine($cachePath, [
                $app->basePath('resources/views'),
            ], ['.stempler.php', '.dark.php']);

            $view->registerEngine('stempler.php', $stempler);
            $view->registerEngine('dark.php', $stempler);

            $view->addLocation($app->basePath('resources/views'));

            $app->instance('view.rendered', false);

            return $view;
        });
    }

    public function boot(): void
    {
        $app = $this->app;
        if ($app->has(\PrestoWorld\Theme\ThemeManager::class)) {
            $themeManager = $app->make(\PrestoWorld\Theme\ThemeManager::class);
            if ($theme = $themeManager->getActiveTheme()) {
                $view = $app->make(ViewFactory::class);
                $themeViews = $theme->getPath() . '/resources/views';

                $view->prependLocation($themeViews);

                foreach ($view->getEngines() as $engine) {
                    if (method_exists($engine, 'prependPath')) {
                        $engine->prependPath($themeViews);
                    }
                }
            }
        }
    }
}
