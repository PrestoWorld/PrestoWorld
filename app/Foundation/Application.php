<?php

declare(strict_types=1);

namespace App\Foundation;

use Witals\Framework\Application as BaseApplication;

/**
 * PrestoWorld Application
 * 
 * This is the main application class for PrestoWorld.
 * It extends the Witals Framework base application.
 */
class Application extends BaseApplication
{
    // You can customize application-level logic here
    
    /**
     * Register configured service providers
     */
    public function registerConfiguredProviders(): void
    {
        parent::registerConfiguredProviders();

        // Bind the HTTP Kernel
        $this->singleton(
            \Witals\Framework\Contracts\Http\Kernel::class,
            \App\Http\Kernel::class
        );

        // Register PrestoWorld specific providers here
        // $this->register(\App\Providers\AppServiceProvider::class);
    }
}
