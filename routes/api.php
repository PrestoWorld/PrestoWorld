<?php

declare(strict_types=1);

use App\Http\Routing\Contracts\RouterInterface;
use App\Http\Controllers\Admin\PostsController;
use App\Http\Controllers\Admin\PluginsController;
use App\Http\Controllers\Admin\ThemesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatabaseHealthController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\MediaController;

/** @var RouterInterface $router */

$router->get('/api/admin/menu', [\App\Http\Controllers\Admin\SpaController::class, 'menu']);
$router->get('/api/admin/dashboard/widgets', [\App\Http\Controllers\Admin\SpaController::class, 'dashboardWidgets']);

$router->get('/api/admin/posts', [PostsController::class, 'posts']);
$router->get('/api/admin/posts/{id}', [PostsController::class, 'getPost']);
$router->post('/api/admin/posts', [PostsController::class, 'createPostApi']);
$router->put('/api/admin/posts/{id}', [PostsController::class, 'updatePostApi']);
$router->get('/api/admin/categories', [PostsController::class, 'categories']);
$router->get('/api/admin/tags', [PostsController::class, 'tags']);
$router->get('/api/admin/plugins', [PluginsController::class, 'plugins']);
$router->get('/api/admin/plugins/browse', [PluginsController::class, 'browse']);
$router->post('/api/admin/plugins/install', [PluginsController::class, 'install']);
$router->post('/api/admin/plugins/toggle', [PluginsController::class, 'toggle']);
$router->post('/api/admin/plugins/update', [PluginsController::class, 'update']);
$router->get('/api/admin/themes', [ThemesController::class, 'themes']);
$router->post('/api/admin/themes/activate', [ThemesController::class, 'activateTheme']);
$router->get('/api/admin/stats', [DashboardController::class, 'stats']);
$router->get('/api/admin/activities', [DashboardController::class, 'activities']);
$router->get('/api/admin/database/health', [DatabaseHealthController::class, 'health']);
$router->get('/api/admin/database/tables', [DatabaseHealthController::class, 'tables']);
$router->get('/api/admin/users', [UsersController::class, 'users']);
$router->get('/api/admin/media', [MediaController::class, 'media']);
$router->post('/api/admin/media/upload', [MediaController::class, 'uploadMedia']);
$router->post('/api/admin/media/{id}/offload', [MediaController::class, 'offloadMedia']);
