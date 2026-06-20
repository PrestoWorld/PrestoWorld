<?php

declare(strict_types=1);

use App\Http\Routing\Contracts\RouterInterface;
use App\Http\Controllers\Admin\AdminApiController;

/** @var RouterInterface $router */

$router->get('/api/admin/menu', [\App\Http\Controllers\Admin\SpaController::class, 'menu']);
$router->get('/api/admin/dashboard/widgets', [\App\Http\Controllers\Admin\SpaController::class, 'dashboardWidgets']);

$router->get('/api/admin/posts', [AdminApiController::class, 'posts']);
$router->get('/api/admin/plugins', [AdminApiController::class, 'plugins']);
$router->get('/api/admin/stats', [AdminApiController::class, 'stats']);
$router->get('/api/admin/activities', [AdminApiController::class, 'activities']);
