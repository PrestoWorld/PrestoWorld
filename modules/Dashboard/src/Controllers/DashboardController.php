<?php

declare(strict_types=1);

namespace Modules\Dashboard\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class DashboardController
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function index(Request $request): Response
    {
        $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);
        $themeManager->loadActiveTheme();
        
        // Data for the dashboard
        $modules = [];
        if ($this->app->has(\App\Foundation\Module\ModuleManager::class)) {
            $modules = $this->app->make(\App\Foundation\Module\ModuleManager::class)->all();
        }

        $html = $themeManager->render('admin-dashboard', [
            'modules' => $modules,
            'title' => 'Dashboard Manager'
        ]);

        return Response::html($html);
    }
}
