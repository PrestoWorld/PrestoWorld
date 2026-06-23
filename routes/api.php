<?php

declare(strict_types=1);

use App\Http\Routing\Contracts\RouterInterface;
use App\Http\Controllers\Admin\AdminApiController;

/** @var RouterInterface $router */

$router->get('/api/admin/menu', [\App\Http\Controllers\Admin\SpaController::class, 'menu']);
$router->get('/api/admin/dashboard/widgets', [\App\Http\Controllers\Admin\SpaController::class, 'dashboardWidgets']);

$router->get('/api/admin/posts', [AdminApiController::class, 'posts']);
$router->get('/api/admin/plugins', [AdminApiController::class, 'plugins']);
$router->get('/api/admin/themes', [AdminApiController::class, 'themes']);
$router->post('/api/admin/themes/activate', [AdminApiController::class, 'activateTheme']);
$router->get('/api/admin/stats', [AdminApiController::class, 'stats']);
$router->get('/api/admin/activities', [AdminApiController::class, 'activities']);
$router->get('/api/admin/users', [AdminApiController::class, 'users']);
$router->get('/api/admin/media', [AdminApiController::class, 'media']);
$router->post('/api/admin/media/upload', [AdminApiController::class, 'uploadMedia']);
$router->post('/api/admin/media/{id}/offload', [AdminApiController::class, 'offloadMedia']);
