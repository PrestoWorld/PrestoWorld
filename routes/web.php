<?php

declare(strict_types=1);

use App\Http\Routing\Contracts\RouterInterface;
use App\Http\Controllers\AuthController;

/** @var RouterInterface $router */

// Auth
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'handleLogin']);
$router->get('/logout', [AuthController::class, 'handleLogout']);

// Admin SPA entry
$router->get('/dashboard', \App\Http\Controllers\Admin\SpaController::class);

// Admin API
$router->get('/api/admin/menu', [\App\Http\Controllers\Admin\SpaController::class, 'menu']);
$router->get('/api/admin/dashboard/widgets', [\App\Http\Controllers\Admin\SpaController::class, 'dashboardWidgets']);

// Homepage
$router->get('/', function () {
    return \Witals\Framework\Http\Response::html('<h1>Hello, PrestoWorld!</h1><p><a href="/login">Login</a> | <a href="/dashboard">Dashboard</a></p>');
});
