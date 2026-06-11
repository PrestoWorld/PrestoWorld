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

// 2. Detect Runtime (FPM, RoadRunner, FrankenPHP, etc.)
$runtime = RuntimeType::detect();

// 3. Initialize Application
$app = new Application(dirname(__DIR__), $runtime);

// 4. Specify Config Path
$app->setConfigPaths('config');

// 5. Create and Start Server
// ServerFactory will return the appropriate server instance based on $runtime
$server = ServerFactory::create($runtime, $app);

$server->start();
