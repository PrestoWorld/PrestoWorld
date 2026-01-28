<?php

declare(strict_types=1);

namespace App\Foundation\Debug;

use App\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(DebugBar::class, function ($app) {
            return new DebugBar($app);
        });
    }

    public function boot(): void
    {
        // Debug bar initialization
    }
}
