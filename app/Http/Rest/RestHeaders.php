<?php

declare(strict_types=1);

namespace App\Http\Rest;

use Witals\Framework\Http\Request;

/**
 * Case-insensitive header lookup.
 *
 * `Request::createFromGlobals()` keeps the casing of the `HTTP_*` server key
 * (`X-WP-NONCE`), PSR-7 requests keep the client's casing (`x-wp-nonce`) and
 * Swoole normalises differently again — so a mixed-case lookup like
 * `header('X-WP-Nonce')` silently misses on some transports.
 */
trait RestHeaders
{
    protected function headerValue(Request $request, string $name): string
    {
        $candidates = [
            $name,
            strtoupper($name),
            strtolower($name),
        ];

        foreach ($candidates as $candidate) {
            $value = $request->header($candidate);
            if (is_string($value) && $value !== '') {
                return $value;
            }
            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        // Fall back to the raw server array (same request, transport-proof).
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $request->server($serverKey);

        return is_scalar($value) ? (string) $value : '';
    }
}
