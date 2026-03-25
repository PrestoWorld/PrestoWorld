<?php

/** @var \App\Http\Routing\Router $router */

error_log("routes/web.php: Registering routes...");

// Define modern Laravel-style routes here
$router->get('/hello-native', function() {
    return \Witals\Framework\Http\Response::html('<h1>Hello from PrestoWorld Native Route!</h1>');
});

$router->get('/api/test', function() {
    return \Witals\Framework\Http\Response::json([
        'status' => 'ok',
        'engine' => 'native',
        'message' => 'This is a modern route'
    ]);
});

// Native home route with Theme Engine (Commented out to use Home Module)
// $router->get('/', function(\Witals\Framework\Http\Request $request) {
//     /** @var \App\Http\Kernel $kernel */
//     $kernel = app(\Witals\Framework\Contracts\Http\Kernel::class);
//     return $kernel->handleHome($request);
// });

// Admin Dashboard route (Commented out to use Dashboard Module)
// $router->get('/admin', function(\Witals\Framework\Http\Request $request) {
//     $themeManager = app(\PrestoWorld\Theme\ThemeManager::class);
//     $themeManager->loadActiveTheme(); 
//     $html = $themeManager->render('admin-dashboard', []);
//     return \Witals\Framework\Http\Response::html($html);
// });

$router->get('/health', function(\Witals\Framework\Http\Request $request) {
    return app(\Witals\Framework\Contracts\Http\Kernel::class)->handleHealth($request);
});

$router->get('/info', function(\Witals\Framework\Http\Request $request) {
    return app(\Witals\Framework\Contracts\Http\Kernel::class)->handleInfo($request);
});

// System Component Management
$router->get('/system/scan', [\App\Http\Controllers\SystemController::class, 'scan']);
$router->get('/system/clear-cache', [\App\Http\Controllers\SystemController::class, 'clearCache']);
