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

// Installation routes (check if installed before serving)
$router->get('/install', [App\Http\Controllers\InstallController::class, 'show']);
$router->get('/install/', [App\Http\Controllers\InstallController::class, 'show']);
$router->get('/install/{any}', [App\Http\Controllers\InstallController::class, 'asset']);

// Routes are dynamically injected by modules and service providers.

