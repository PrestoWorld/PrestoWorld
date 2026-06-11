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
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PrestoWorld &mdash; Setup Required</title>
        <style>
            :root {
                --bg: #0f172a;
                --card-bg: rgba(30, 41, 59, 0.7);
                --text: #f8fafc;
                --text-muted: #94a3b8;
                --accent: #6366f1;
                --accent-hover: #4f46e5;
                --code-bg: #1e293b;
            }
            body {
                background: var(--bg);
                color: var(--text);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
                overflow: hidden;
            }
            .grid-bg {
                position: absolute;
                inset: 0;
                background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
                background-size: 30px 30px;
                mask-image: linear-gradient(to bottom, transparent, black 10%, black 90%, transparent);
                z-index: -1;
            }
            .card {
                background: var(--card-bg);
                backdrop-filter: blur(24px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 24px;
                padding: 3rem;
                max-width: 480px;
                text-align: center;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                animation: fadeInUp 0.8s ease-out;
            }
            h1 {
                font-size: 1.8rem;
                font-weight: 700;
                margin: 0 0 1rem;
                background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            p {
                color: var(--text-muted);
                line-height: 1.6;
                margin-bottom: 2rem;
            }
            .code-block {
                background: var(--code-bg);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                padding: 1rem;
                margin-bottom: 2rem;
                font-family: 'JetBrains Mono', monospace;
                font-size: 0.9rem;
                color: #a5b4fc;
                display: flex;
                align-items: center;
                justify-content: space-between;
                cursor: pointer;
                transition: all 0.2s;
            }
            .code-block:hover {
                border-color: var(--accent);
                box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
            }
            .btn {
                background: var(--accent);
                color: white;
                text-decoration: none;
                padding: 12px 32px;
                border-radius: 99px;
                font-weight: 600;
                font-size: 0.95rem;
                transition: all 0.2s;
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            }
            .btn:hover {
                background: var(--accent-hover);
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(99, 102, 241, 0.5);
            }
            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body>
        <div class="grid-bg"></div>
        <div class="card">
            <h1>Dependencies Missing</h1>
            <p>PrestoWorld requires third-party packages to run. Please install dependencies using Composer to get started.</p>
            
            <div class="code-block" onclick="navigator.clipboard.writeText('composer install'); this.style.borderColor='#4ade80'; setTimeout(() => this.style.borderColor='rgba(255,255,255,0.1)', 1000)">
                $ composer install
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="opacity:0.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
            </div>

            <button class="btn" onclick="window.location.reload()">I've Installed Them &rarr;</button>
        </div>
    </body>
    </html>
    <?php
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

// Check for .env or remarkably small .env (nearly empty)
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath) || (file_exists($envPath) && filesize($envPath) < 10)) {
    require_once __DIR__ . '/setup.php';
    exit;
}

// Bootstrap the application
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Create and start the traditional server using the factory
$server = ServerFactory::create(RuntimeType::TRADITIONAL, $app);
$server->start();
