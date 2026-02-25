<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'name' => env('APP_NAME', 'PrestoWorld'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */
    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    */
    'debug' => env('APP_DEBUG', false) === 'true' || env('APP_DEBUG', false) === true,

    /*
    |--------------------------------------------------------------------------
    | Localization Configuration
    |--------------------------------------------------------------------------
    */
    'locale' => 'en',
    'locales' => ['en', 'ja', 'ko', 'fr', 'vi'],

    /*
    |--------------------------------------------------------------------------
    | Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */
    'providers' => [
        // Core Providers
        App\Providers\LogServiceProvider::class,
        App\Providers\HookServiceProvider::class,
        App\Providers\DatabaseServiceProvider::class,
        PrestoWorld\Theme\ThemeServiceProvider::class,
        PrestoWorld\Context\ContextServiceProvider::class,
        App\Foundation\Debug\DebugServiceProvider::class,
        Witals\Framework\Auth\AuthServiceProvider::class,
        Witals\Framework\Database\Crud\CrudServiceProvider::class,
        Witals\Framework\Seo\SeoServiceProvider::class,
        // Core Framework Services (Ecommerce, Payments)
        PrestoWorld\Ecommerce\EcommerceServiceProvider::class,
        PrestoWorld\Payments\PaymentServiceProvider::class,
        
        // WordPress Bridge (loads helpers early)
        PrestoWorld\Bridge\WordPress\Providers\WordPressServiceProvider::class,
        Modules\WebServices\WebServicesServiceProvider::class,
        Modules\ReminderManager\ReminderManagerServiceProvider::class,
        Modules\WebsiteTemplates\WebsiteTemplatesServiceProvider::class,
        
        App\Providers\RouteServiceProvider::class,
        App\Providers\ViewServiceProvider::class,
        PrestoWorld\Admin\Providers\AdminServiceProvider::class,
        App\Providers\ConsoleServiceProvider::class,
    ],
];
