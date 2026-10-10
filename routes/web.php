<?php

declare(strict_types=1);

use App\Foundation\Application;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;

/**
 * Check if PrestoWorld is already installed.
 */
if (!function_exists('isInstalled')) {
    function isInstalled(): bool
    {
        $app = new Application(base_path());
        $app->boot();

        try {
            /** @var DatabaseProviderInterface $dbal */
            $dbal = $app->make(DatabaseProviderInterface::class);
            $db = $dbal->database();
            $tablePrefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
            $optionsTable = $tablePrefix . 'options';

            if (!$db->hasTable($optionsTable)) {
                return false;
            }

            $stmt = $db->prepare("SELECT option_value FROM {$optionsTable} WHERE option_name = ?");
            $stmt->execute(['presto_installed']);
            $row = $stmt->fetch();

            return $row && $row['option_value'] === '1';
        } catch (\Throwable $e) {
            // If we cannot determine, assume not installed to allow installation attempt
            return false;
        }
    }
}

// Auth
$router->get('/login', [App\Http\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [App\Http\Controllers\AuthController::class, 'handleLogin']);
$router->get('/logout', [App\Http\Controllers\AuthController::class, 'handleLogout']);

// Admin SPA entry
$router->get('/dashboard', \App\Http\Controllers\Admin\SpaController::class);

// Installation routes (check if installed before serving)
$router->get('/install', function () {
    if (isInstalled()) {
        return Response::redirect('/login');
    }

    $path = base_path('public/installer/install.html');
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $mime = mime_content_type($path);
        return new Response($content, 200, ['Content-Type' => $mime]);
    }

    return Response::html('', 404);
});

$router->get('/install/', function () {
    if (isInstalled()) {
        return Response::redirect('/login');
    }

    $path = base_path('public/installer/install.html');
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $mime = mime_content_type($path);
        return new Response($content, 200, ['Content-Type' => $mime]);
    }

    return Response::html('', 404);
});

$router->get('/install/{any}', function ($any) {
    if (isInstalled()) {
        return Response::redirect('/login');
    }

    $path = base_path('public/installer/'.$any);
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $mime = mime_content_type($path);
        return new Response($content, 200, ['Content-Type' => $mime]);
    }

    return Response::html('', 404);
});

// Routes are dynamically injected by modules and service providers.

