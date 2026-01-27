<?php

/**
 * Entry point for traditional web servers (Apache, Nginx, PHP built-in server)
 * This file handles requests when running on traditional PHP-FPM/CGI
 */

declare(strict_types=1);

use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Server\ServerFactory;

define('WITALS_START', microtime(true));

// Register the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap the application
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Create and start the traditional server using the factory
$server = ServerFactory::create(RuntimeType::TRADITIONAL, $app);
$server->start();
