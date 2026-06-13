<?php

declare(strict_types=1);

use App\Http\Routing\Contracts\RouterInterface;

/** @var RouterInterface $router */

// Admin SPA entry
$router->get('/dashboard', \App\Http\Controllers\Admin\SpaController::class);

// Admin API
$router->get('/api/admin/menu', [\App\Http\Controllers\Admin\SpaController::class, 'menu']);
$router->get('/api/admin/dashboard/widgets', [\App\Http\Controllers\Admin\SpaController::class, 'dashboardWidgets']);
