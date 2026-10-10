<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;

/**
 * Ensure PrestoWorld is installed before processing web requests.
 * Redirects to /install if not installed (similar to WordPress setup).
 */
class EnsureInstalledMiddleware
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $request, callable $next): Response
    {
        $path = '/' . ltrim($request->path(), '/');

        // Allow access to installer pages, setup APIs, and static assets
        if (
            str_starts_with($path, '/install') ||
            str_starts_with($path, '/api/setup') ||
            str_starts_with($path, '/assets') ||
            str_starts_with($path, '/content') ||
            $path === '/favicon.ico'
        ) {
            return $next($request);
        }

        if (!$this->isInstalled()) {
            return Response::redirect('/install');
        }

        return $next($request);
    }

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

            $stmt = $db->prepare("SELECT option_value FROM {$optionsTable} WHERE option_name = ?");
            $stmt->execute(['presto_installed']);
            $row = $stmt->fetch();

            return $row && $row['option_value'] === '1';
        } catch (\Throwable) {
            return false;
        }
    }
}
