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


