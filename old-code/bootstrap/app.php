<?php

declare(strict_types=1);

use App\Foundation\Application;
use Dotenv\Dotenv;
use Witals\Framework\Contracts\RuntimeType;

$basePath = dirname(__DIR__);

$envPath = $basePath . '/.env';
if (file_exists($envPath)) {
    $dotenv = Dotenv::createImmutable($basePath);
    $dotenv->safeLoad();
}

$runtime = null;
if (defined('WITALS_RUNTIME')) {
    $runtime = RuntimeType::from(WITALS_RUNTIME);
}

$app = new Application(
    basePath: $basePath,
    runtime: $runtime
);

$app->singleton(
    \Witals\Framework\Contracts\Http\Kernel::class,
    \App\Http\Kernel::class
);

$providers = $app->config('app.providers', []);
foreach ($providers as $provider) {
    $app->register($provider);
}

if ($app->isLongRunning()) {
    ini_set('session.auto_start', '0');
    gc_enable();
    if (!ini_get('memory_limit') || ini_get('memory_limit') === '-1') {
        ini_set('memory_limit', '256M');
    }
}

return $app;
