<?php

declare(strict_types=1);

namespace Modules\Affiliates;

use App\Support\ServiceProvider;
use App\Http\Routing\Router;

class AffiliatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Controllers\AffiliateController::class, fn($app) => new Controllers\AffiliateController($app));
        $this->singleton(Controllers\AffiliateAdminController::class, fn($app) => new Controllers\AffiliateAdminController($app));
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // --- Frontend Affiliate Portal ---
        $router->get('/affiliates',           [Controllers\AffiliateController::class, 'index']);
        $router->get('/affiliates/commissions', [Controllers\AffiliateController::class, 'commissions']);
        $router->get('/affiliates/withdraw',    [Controllers\AffiliateController::class, 'withdraw']);
        $router->get('/affiliates/vouchers',    [Controllers\AffiliateController::class, 'vouchers']);
        $router->get('/affiliates/plans',       [Controllers\AffiliateController::class, 'plans']);
        
        // --- Portal Affiliate Routes ---
        $router->get('/portal/affiliates',             [Controllers\AffiliateController::class, 'index']);
        $router->get('/portal/affiliates/commissions', [Controllers\AffiliateController::class, 'commissions']);
        $router->get('/portal/affiliates/withdraw',    [Controllers\AffiliateController::class, 'withdraw']);
        $router->get('/portal/affiliates/vouchers',    [Controllers\AffiliateController::class, 'vouchers']);
        $router->get('/portal/affiliates/plans',       [Controllers\AffiliateController::class, 'plans']);
        
        // Tracking Link Handler
        $router->get('/go/{campaign}',         [Controllers\AffiliateController::class, 'track']);

        // --- Admin UI ---
        $router->get('/dashboard/affiliates',            [Controllers\AffiliateAdminController::class, 'index']);
        $router->get('/dashboard/affiliates/commissions', [Controllers\AffiliateAdminController::class, 'commissions']);
    }
}
