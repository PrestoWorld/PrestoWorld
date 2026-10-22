<?php

declare(strict_types=1);

namespace App\Http\Rest;

use App\Foundation\Application;

/**
 * WordPress-compatible REST nonce (`X-WP-Nonce`).
 *
 * The editor's `@wordpress/api-fetch` expects `createNonceMiddleware` output:
 * a stable per-session value that the browser echoes in a header on write
 * requests. Here it is derived deterministically from the auth cookie so the
 * issuing page and the middleware on the API side always agree without any
 * shared server state.
 */
final class WpNonce
{
    public const HEADER = 'X-WP-Nonce';
    public const ACTION = 'wp_rest';

    private function __construct()
    {
    }

    public static function create(?Application $app = null): string
    {
        return substr(hash_hmac('sha256', self::ACTION . ':' . self::seed(), self::key($app)), 0, 32);
    }

    public static function verify(string $nonce, ?Application $app = null): bool
    {
        if ($nonce === '') {
            return false;
        }

        return hash_equals(self::create($app), $nonce);
    }

    private static function seed(): string
    {
        return (string) ($_COOKIE['token'] ?? '');
    }

    private static function key(?Application $app): string
    {
        unset($app);
        // Derive from the install path (this file always sits in
        // <root>/app/Http/Rest): identical whether the value is computed by
        // the page that issues the nonce, by the middleware that validates it,
        // or from a CLI script — no dependency on cwd or DOCUMENT_ROOT.
        return hash('sha256', dirname(__DIR__, 3) . '|wp-rest-nonce');
    }
}
