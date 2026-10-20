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

$router->get('/api/admin/initial-state', [\App\Http\Controllers\Admin\SpaController::class, 'initialState']);
$router->get('/api/admin/menu', [\App\Http\Controllers\Admin\SpaController::class, 'menu']);
$router->get('/api/admin/dashboard/widgets', [\App\Http\Controllers\Admin\SpaController::class, 'dashboardWidgets']);
$router->get('/api/admin/screens/{screenId}', [\App\Http\Controllers\Admin\SpaController::class, 'screenData']);

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

// Settings routes
->get("/api/admin/settings/general", [\App\Http\Controllers\Admin\SettingsController::class, "getGeneral"]);
->get("/api/admin/settings/writing", [\App\Http\Controllers\Admin\SettingsController::class, "getWriting"]);
->get("/api/admin/settings/reading", [\App\Http\Controllers\Admin\SettingsController::class, "getReading"]);
->get("/api/admin/settings/discussion", [\App\Http\Controllers\Admin\SettingsController::class, "getDiscussion"]);
->get("/api/admin/settings/media", [\App\Http\Controllers\Admin\SettingsController::class, "getMedia"]);
->get("/api/admin/settings/permalink", [\App\Http\Controllers\Admin\SettingsController::class, "getPermalink"]);
->get("/api/admin/settings/privacy", [\App\Http\Controllers\Admin\SettingsController::class, "getPrivacy"]);
->post("/api/admin/settings/general", [\App\Http\Controllers\Admin\SettingsController::class, "saveGeneral"]);
->post("/api/admin/settings/writing", [\App\Http\Controllers\Admin\SettingsController::class, "saveWriting"]);
->post("/api/admin/settings/reading", [\App\Http\Controllers\Admin\SettingsController::class, "saveReading"]);
->post("/api/admin/settings/discussion", [\App\Http\Controllers\Admin\SettingsController::class, "saveDiscussion"]);
->post("/api/admin/settings/media", [\App\Http\Controllers\Admin\SettingsController::class, "saveMedia"]);
->post("/api/admin/settings/permalink", [\App\Http\Controllers\Admin\SettingsController::class, "savePermalink"]);
->post("/api/admin/settings/privacy", [\App\Http\Controllers\Admin\SettingsController::class, "savePrivacy"]);

// Setup routes (accessible before installation)
$router->post('/api/setup/install', [\App\Http\Controllers\Api\SetupController::class, 'install']);

// Setup routes (accessible before installation)
$router->get('/api/setup/check-installed', [\App\Http\Controllers\Api\SetupController::class, 'checkInstalled']);
$router->get('/api/setup/env', [\App\Http\Controllers\Api\SetupController::class, 'env']);
$router->post('/api/setup/test-db', [\App\Http\Controllers\Api\SetupController::class, 'testDb']);
$router->get('/api/setup/themes', [\App\Http\Controllers\Api\SetupController::class, 'themes']);
$router->get('/api/setup/modules', [\App\Http\Controllers\Api\SetupController::class, 'modules']);
