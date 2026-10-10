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
 * Handles serving the installer SPA and processing installation via API (though API is separate).
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

        $path = $this->app->basePath('public/installer/install.html');
        if (file_exists($path)) {
            $content = file_get_contents($path);
            return new Response($content, 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        return Response::html('Installer not found.', 404);
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

            $row = $db->query("SELECT option_value FROM {$optionsTable} WHERE option_name = ?", ['presto_installed'])->fetch();

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
}
