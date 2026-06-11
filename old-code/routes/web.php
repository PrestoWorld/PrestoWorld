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

// --- Marketplace & Extensions Admin UI ---
$router->get('/dashboard/plugins', [\App\Foundation\Admin\Controllers\MarketplaceController::class, 'installedPlugins']);
$router->get('/dashboard/plugins/install', [\App\Foundation\Admin\Controllers\MarketplaceController::class, 'plugins']);
$router->get('/dashboard/themes', [\App\Foundation\Admin\Controllers\MarketplaceController::class, 'themes']);
$router->get('/dashboard/themes/install', [\App\Foundation\Admin\Controllers\MarketplaceController::class, 'installThemes']);

// --- CMS: Product Management ---
$router->get('/dashboard/products', [\App\Foundation\Admin\Controllers\ProductController::class, 'index']);
$router->get('/dashboard/products/create', [\App\Foundation\Admin\Controllers\ProductController::class, 'create']);
$router->post('/dashboard/products/store', [\App\Foundation\Admin\Controllers\ProductController::class, 'store']);
$router->get('/dashboard/products/edit/{id}', [\App\Foundation\Admin\Controllers\ProductController::class, 'edit']);
$router->post('/dashboard/products/update/{id}', [\App\Foundation\Admin\Controllers\ProductController::class, 'update']);
$router->post('/dashboard/products/delete/{id}', [\App\Foundation\Admin\Controllers\ProductController::class, 'delete']);

// --- CMS: Category Management ---
$router->get('/dashboard/categories', [\App\Foundation\Admin\Controllers\CategoryController::class, 'index']);
$router->get('/dashboard/categories/create', [\App\Foundation\Admin\Controllers\CategoryController::class, 'create']);
$router->post('/dashboard/categories/store', [\App\Foundation\Admin\Controllers\CategoryController::class, 'store']);
$router->get('/dashboard/categories/edit/{id}', [\App\Foundation\Admin\Controllers\CategoryController::class, 'edit']);
$router->post('/dashboard/categories/update/{id}', [\App\Foundation\Admin\Controllers\CategoryController::class, 'update']);
$router->post('/dashboard/categories/delete/{id}', [\App\Foundation\Admin\Controllers\CategoryController::class, 'delete']);
$router->get('/admin/plugins/install', [\App\Foundation\Admin\Controllers\MarketplaceController::class, 'plugins']);
$router->get('/admin/themes', [\App\Foundation\Admin\Controllers\MarketplaceController::class, 'themes']);
$router->get('/admin/themes/install', [\App\Foundation\Admin\Controllers\MarketplaceController::class, 'installThemes']);
