<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * RedirectService — wp_redirect/wp_safe_redirect (spec 10 §10.4).
 */
final class RedirectService
{
    private function __construct()
    {
    }

    public static function to(string $location, int $status = 302): bool
    {
        if ($location === '') {
            return false;
        }

        if (!headers_sent()) {
            header('Location: ' . Escape::urlRaw($location), true, $status);
        }

        return true;
    }

    public static function safe(string $location, int $status = 302): bool
    {
        if (!self::isAllowedHost($location)) {
            return self::to(Config::string('home_url'), $status);
        }

        return self::to($location, $status);
    }

    public static function sanitize(string $location): string
    {
        return self::isAllowedHost($location) ? $location : '/';
    }

    private static function isAllowedHost(string $location): bool
    {
        $parts = parse_url($location);
        if ($parts === false) {
            return false;
        }

        $host = $parts['host'] ?? null;
        if ($host === null) {
            return true;
        }

        $allowedHost = parse_url(Config::string('home_url'), PHP_URL_HOST);

        return $allowedHost !== null && strtolower($host) === strtolower($allowedHost);
    }
}