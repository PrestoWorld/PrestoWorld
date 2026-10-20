<?php

declare(strict_types=1);

use Witals\Framework\Http\Response;

/* TEST ROUTE */
$router->get('/test', function () {
    return Response::html('test');
});

// Auth
$router->get('/login', [App\Http\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [App\Http\Controllers\AuthController::class, 'handleLogin']);
$router->get('/logout', [App\Http\Controllers\AuthController::class, 'handleLogout']);

// Admin SPA entry — dynamic routes: /dashboard, /dashboard/{screen}
$router->get('/dashboard/{screen?::[a-z0-9\-]+}', [\App\Http\Controllers\Admin\SpaController::class, 'dashboard']);

// Context Editor — standalone Site Editor (không qua admin SPA)
$router->get('/site-editor', [\App\Http\Controllers\SiteEditorController::class, 'show']);
$router->get('/site-editor/{contextType::[a-z0-9\-]+}', [\App\Http\Controllers\SiteEditorController::class, 'show']);
$router->get('/site-editor/assets/{path::.*}', [\App\Http\Controllers\SiteEditorController::class, 'asset']);

// Static content files (theme/plugin assets) — extension whitelist only,
// Static content files (theme/plugin assets) - extension whitelist only,
// PHP sources are never exposed or executed (see ContentFileController).
$router->get('/content/{path::.+}', [\App\Http\Controllers\ContentFileController::class, 'serve']);

// Installation routes (check if installed before serving)
$router->get('/install', [App\Http\Controllers\InstallController::class, 'show']);
$router->get('/install/', [App\Http\Controllers\InstallController::class, 'show']);
$router->get('/install/{any}', [App\Http\Controllers\InstallController::class, 'asset']);

// Routes are dynamically injected by modules and service providers.

