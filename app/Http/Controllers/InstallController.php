<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Psr\Log\LoggerInterface;
use Cycle\Database\DatabaseProviderInterface;

/**
 * Controller for the installation process.
 * Handles serving the installer SPA rendered directly from controller (no template files).
 */
class InstallController
{
    protected Application $app;
    protected LoggerInterface $logger;

    public function __construct(Application $app, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    /**
     * Show the installer SPA if not installed, otherwise redirect to login.
     */
    public function show(): Response
    {
        // Boot the application to check installation status and access services.
        $this->app->boot();

        if ($this->isInstalled()) {
            return Response::redirect('/login');
        }

        $html = $this->getInstallerHtml();
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Serve installer assets (JS, CSS, etc.) if not installed.
     */
    public function asset(string $any): Response
    {
        $this->app->boot();

        if ($this->isInstalled()) {
            return Response::redirect('/login');
        }

        $safePath = str_replace(['..', "\0"], '', $any);
        $path = $this->app->basePath("public/installer/{$safePath}");
        if (file_exists($path) && is_file($path)) {
            $content = file_get_contents($path);
            $mime = $this->getMimeType($path);
            return new Response($content, 200, ['Content-Type' => $mime]);
        }

        return Response::html('File not found.', 404);
    }

    /**
     * Get accurate MIME type for installer assets.
     */
    protected function getMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'js' => 'application/javascript; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            'html', 'htm' => 'text/html; charset=utf-8',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            default => mime_content_type($path) ?: 'application/octet-stream',
        };
    }

    /**
     * Check if PrestoWorld is already installed.
     */
    protected function isInstalled(): bool
    {
        try {
            /** @var DatabaseProviderInterface $dbal */
            $dbal = $this->app->make(DatabaseProviderInterface::class);
            $db = $dbal->database();
            $tablePrefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
            $optionsTable = $tablePrefix . 'options';

            if (!$db->hasTable($optionsTable)) {
                return false;
            }

            $row = $db->select('option_value')
                ->from($optionsTable)
                ->where('option_name', 'presto_installed')
                ->run()
                ->fetch();

            return $row && $row['option_value'] === '1';
        } catch (\Throwable $e) {
            // If we cannot determine, assume not installed to allow installation attempt
            $this->logger->error('Installation check failed: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Get the full WordPress-like installer HTML.
     */
    protected function getInstallerHtml(): string
    {
        $lang = 'en';
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrestoWorld &rsaquo; Setup Configuration File</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f1f1f1; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; color: #444; line-height: 1.5; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.13); }
        .header { background: #f7f7f7; border-bottom: 1px solid #ddd; padding: 24px 30px; text-align: center; }
        .header h1 { font-size: 22px; font-weight: 400; color: #222; margin-bottom: 8px; }
        .header p { font-size: 13px; color: #666; }
        .content { padding: 30px; }
        .step-indicator { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .step { display: inline-block; margin: 0 15px; color: #666; font-size: 13px; }
        .step.active { color: #0073aa; font-weight: 600; }
        .step.completed { color: #79ba49; }
        .form-group { margin-bottom: 24px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #23282d; font-size: 14px; }
        .description { font-size: 12px; color: #666; margin-top: 4px; }
        input[type="text"], input[type="password"], input[type="email"], select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; font-family: inherit; }
        input:focus, select:focus { border-color: #5b9dd9; box-shadow: 0 0 2px rgba(30,140,190,0.8); outline: none; }
        .button { background: #0073aa; color: #fff; border: 1px solid #006799; padding: 12px 24px; font-size: 14px; border-radius: 4px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .button:hover { background: #005177; border-color: #004e6c; }
        .button-secondary { background: #f7f7f7; color: #555; border-color: #ccc; }
        .button-secondary:hover { background: #e9e9e9; color: #23282d; border-color: #999; }
        .nav-buttons { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; display: flex; justify-content: space-between; }
        .error { background: #fbeaea; border-left: 4px solid #dc3232; padding: 12px 16px; margin-bottom: 20px; color: #a00; font-size: 13px; }
        .success { background: #edfaef; border-left: 4px solid #46b450; padding: 12px 16px; margin-bottom: 20px; color: #007017; font-size: 13px; }
        .requirements { background: #f9f9f9; border: 1px solid #e5e5e5; padding: 16px; margin-bottom: 20px; font-size: 13px; }
        .requirements h3 { font-size: 14px; margin-bottom: 10px; color: #23282d; }
        .requirements ul { list-style: none; }
        .requirements li { padding: 4px 0; color: #666; }
        .hidden { display: none; }
        .text-center { text-align: center; }
        .spinner { border: 3px solid #f3f3f3; border-top: 3px solid #0073aa; border-radius: 50%; width: 32px; height: 32px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .success-check { display: flex; justify-content: center; }
        .success-check-circle { stroke: #46b450; stroke-width: 2; stroke-dasharray: 166; stroke-dashoffset: 166; animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards; }
        .success-check-mark { stroke: #46b450; stroke-width: 4; stroke-linecap: round; stroke-dasharray: 48; stroke-dashoffset: 48; animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards; }
        @keyframes stroke { 100% { stroke-dashoffset: 0; } }
        .success-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .success-footnote { color: #999; font-size: 12px; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PrestoWorld</h1>
            <p>Setup Configuration File</p>
        </div>
        <div class="content">
            <div id="step-welcome">
                <div class="text-center" style="margin-bottom: 30px;">
                    <h2 style="font-size: 20px; font-weight: 400; color: #222; margin-bottom: 10px;">Welcome to PrestoWorld</h2>
                    <p style="color: #666; font-size: 14px;">Before getting started, we need some information on the database. You will need to know the following items before proceeding.</p>
                </div>
                <div class="requirements">
                    <h3>Database Information</h3>
                    <ul>
                        <li>1. Database name</li>
                        <li>2. Database username</li>
                        <li>3. Database password</li>
                        <li>4. Database host</li>
                        <li>5. Table prefix (if you want to run more than one PrestoWorld in a single database)</li>
                    </ul>
                </div>
                <p style="color: #666; font-size: 13px; margin-bottom: 30px;">We’re going to use this information to create a <code>.env</code> file. You may also simply open <code>.env.example</code> in a text editor, fill in your information, and save it as <code>.env</code>.</p>
                <div class="nav-buttons" style="justify-content: flex-end;">
                    <button class="button" onclick="goToStep('database')">Let's go!</button>
                </div>
            </div>

            <div id="step-database" class="hidden">
                <div class="step-indicator">
                    <span class="step active">1. Database</span>
                    <span class="step">2. Site Setup</span>
                    <span class="step">3. Install</span>
                </div>
                <div id="db-error" class="error hidden"></div>
                <div class="form-group">
                    <label for="db_connection">Database Type</label>
                    <select id="db_connection" onchange="updateDbFields()">
                        <option value="mysql" selected>MySQL</option>
                        <option value="pgsql">PostgreSQL</option>
                        <option value="sqlite">SQLite</option>
                    </select>
                </div>
                <div id="sqlite-fields" class="hidden">
                    <div class="form-group">
                        <label for="db_name_sqlite">Database File Path</label>
                        <input type="text" id="db_name_sqlite" value="database.sqlite">
                    </div>
                </div>
                <div id="mysql-pgsql-fields">
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" placeholder="database_name_here">
                    </div>
                    <div class="form-group">
                        <label for="db_username">Username</label>
                        <input type="text" id="db_username" placeholder="username_here">
                    </div>
                    <div class="form-group">
                        <label for="db_password">Password</label>
                        <input type="password" id="db_password" placeholder="password_here">
                    </div>
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" value="localhost">
                    </div>
                    <div class="form-group">
                        <label for="db_port">Port</label>
                        <input type="text" id="db_port" value="3306">
                    </div>
                    <div class="form-group">
                        <label for="db_prefix">Table Prefix</label>
                        <input type="text" id="db_prefix" value="pw_">
                    </div>
                </div>
                <div class="nav-buttons">
                    <button class="button button-secondary" onclick="goToStep('welcome')">Back</button>
                    <button class="button" onclick="testAndProceed()">Submit</button>
                </div>
            </div>

            <div id="step-site" class="hidden">
                <div class="step-indicator">
                    <span class="step completed">1. Database</span>
                    <span class="step active">2. Site Setup</span>
                    <span class="step">3. Install</span>
                </div>
                <div id="site-error" class="error hidden"></div>
                <div class="form-group">
                    <label for="site_title">Site Title</label>
                    <input type="text" id="site_title" placeholder="My PrestoWorld Site">
                </div>
                <div class="form-group">
                    <label for="admin_username">Admin Username</label>
                    <input type="text" id="admin_username" placeholder="admin">
                </div>
                <div class="form-group">
                    <label for="admin_password">Admin Password</label>
                    <input type="password" id="admin_password">
                </div>
                <div class="form-group">
                    <label for="admin_email">Admin Email</label>
                    <input type="email" id="admin_email" placeholder="admin@example.com">
                </div>
                <div class="nav-buttons">
                    <button class="button button-secondary" onclick="goToStep('database')">Back</button>
                    <button class="button" onclick="startInstall()">Install PrestoWorld</button>
                </div>
            </div>

            <div id="step-install" class="hidden">
                <div class="step-indicator">
                    <span class="step completed">1. Database</span>
                    <span class="step completed">2. Site Setup</span>
                    <span class="step active">3. Install</span>
                </div>
                <div class="text-center">
                    <h2 style="font-size: 18px; margin-bottom: 12px; color: #222;">Installing PrestoWorld...</h2>
                    <p style="color: #666; font-size: 13px; margin-bottom: 20px;">Please wait while we set up your site.</p>
                    <div id="install-spinner" class="spinner"></div>
                    <div id="install-status" style="color: #666; font-size: 13px; margin-top: 16px;"></div>
                    <div id="install-error" class="error hidden" style="text-align: left; margin-top: 20px;"></div>
                </div>
            </div>

            <div id="step-done" class="hidden">
                <div class="text-center">
                    <div class="success-check" aria-hidden="true">
                        <svg viewBox="0 0 52 52" width="72" height="72">
                            <circle class="success-check-circle" cx="26" cy="26" r="25" fill="none"/>
                            <path class="success-check-mark" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                        </svg>
                    </div>
                    <h2 style="font-size: 22px; font-weight: 400; color: #23282d; margin: 18px 0 10px;">Success!</h2>
                    <p style="color: #444; font-size: 14px; margin-bottom: 6px;">PrestoWorld has been installed. <strong>Thank you for choosing PrestoWorld!</strong></p>
                    <p style="color: #666; font-size: 13px; margin-bottom: 24px;">We truly appreciate you being part of our community. Your site is ready &mdash; we hope you love building with it.</p>
                    <div class="success-actions">
                        <a href="/login" class="button">Log In</a>
                        <a href="/" class="button button-secondary">Visit Site</a>
                    </div>
                    <p class="success-footnote">Need help getting started? Check out the documentation anytime.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let installData = {};
        function goToStep(step) {
            document.getElementById('step-welcome').classList.toggle('hidden', step !== 'welcome');
            document.getElementById('step-database').classList.toggle('hidden', step !== 'database');
            document.getElementById('step-site').classList.toggle('hidden', step !== 'site');
            document.getElementById('step-install').classList.toggle('hidden', step !== 'install');
            document.getElementById('step-done').classList.toggle('hidden', step !== 'done');
        }
        function updateDbFields() {
            const conn = document.getElementById('db_connection').value;
            const sqlite = document.getElementById('sqlite-fields');
            const mysqlPgsql = document.getElementById('mysql-pgsql-fields');
            const port = document.getElementById('db_port');
            if (conn === 'sqlite') {
                sqlite.classList.remove('hidden');
                mysqlPgsql.classList.add('hidden');
            } else if (conn === 'pgsql') {
                sqlite.classList.add('hidden');
                mysqlPgsql.classList.remove('hidden');
                port.value = '5432';
            } else {
                sqlite.classList.add('hidden');
                mysqlPgsql.classList.remove('hidden');
                port.value = '3306';
            }
        }
        async function testAndProceed() {
            const errorDiv = document.getElementById('db-error');
            errorDiv.classList.add('hidden');
            const conn = document.getElementById('db_connection').value;
            let payload;
            if (conn === 'sqlite') {
                payload = { connection: 'sqlite', host: 'localhost', port: 0, name: document.getElementById('db_name_sqlite').value, username: 'root', password: '' };
            } else {
                payload = {
                    connection: conn,
                    host: document.getElementById('db_host').value,
                    port: document.getElementById('db_port').value,
                    name: document.getElementById('db_name').value,
                    username: document.getElementById('db_username').value,
                    password: document.getElementById('db_password').value
                };
            }
            try {
                const res = await fetch('/api/setup/test-db', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    installData.db = payload;
                    installData.db_prefix = document.getElementById('db_prefix').value;
                    goToStep('site');
                } else {
                    errorDiv.textContent = 'Connection failed: ' + (data.error || 'Unknown error');
                    errorDiv.classList.remove('hidden');
                }
            } catch (e) {
                errorDiv.textContent = 'Connection failed: ' + e.message;
                errorDiv.classList.remove('hidden');
            }
        }
        async function startInstall() {
            const errorDiv = document.getElementById('install-error');
            const spinner = document.getElementById('install-spinner');
            const status = document.getElementById('install-status');
            errorDiv.classList.add('hidden');
            spinner.classList.remove('hidden');
            status.textContent = 'Running installation...';
            installData.site_title = document.getElementById('site_title').value;
            installData.admin_username = document.getElementById('admin_username').value;
            installData.admin_password = document.getElementById('admin_password').value;
            installData.admin_email = document.getElementById('admin_email').value;
            const payload = {
                db_connection: installData.db.connection,
                db_host: installData.db.host,
                db_port: installData.db.port,
                db_name: installData.db.name,
                db_username: installData.db.username,
                db_password: installData.db.password,
                db_prefix: installData.db_prefix,
                site_title: installData.site_title,
                admin_username: installData.admin_username,
                admin_password: installData.admin_password,
                admin_email: installData.admin_email
            };
            try {
                const res = await fetch('/api/setup/install', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (res.ok && data.success) {
                    goToStep('done');
                } else {
                    spinner.classList.add('hidden');
                    errorDiv.textContent = 'Installation failed: ' + (data.error || 'Unknown error');
                    errorDiv.classList.remove('hidden');
                }
            } catch (e) {
                spinner.classList.add('hidden');
                errorDiv.textContent = 'Installation failed: ' + e.message;
                errorDiv.classList.remove('hidden');
            }
        }
        goToStep('welcome');
    </script>
</body>
</html>
HTML;
    }

}