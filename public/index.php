<?php

/**
 * PrestoWorld - DigitalCore Entry Point
 * 
 * This file serves as the main entry point for all runtimes:
 * - Traditional (CGI/FPM/Litespeed)
 * - RoadRunner
 * - FrankenPHP
 */

declare(strict_types=1);

use App\Foundation\Application;
use Witals\Framework\Server\ServerFactory;
use Witals\Framework\Contracts\RuntimeType;

// 1. Load Composer Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load Environment Variables from .env
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

// Clear OPcache to pick up fresh .env values in config files
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// 3. Configure Error Display — respects PW_DEBUG or APP_DEBUG
$pwDebug = getenv('PW_DEBUG') === '1' || getenv('PW_DEBUG') === 'true';
$appDebug = getenv('APP_DEBUG') === '1' || getenv('APP_DEBUG') === 'true';

if ($pwDebug || $appDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// 4. Detect Runtime (FPM, RoadRunner, FrankenPHP, etc.)
$runtime = RuntimeType::detect();

// 3. Initialize Application
$app = new Application(dirname(__DIR__), $runtime);

// 5. Specify Config Path
$app->setConfigPaths('config');

// 6. Create and Start Server
// ServerFactory will return the appropriate server instance based on $runtime
$server = ServerFactory::create($runtime, $app);

$server->start();
