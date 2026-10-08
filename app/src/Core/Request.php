<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Request — is_admin/is_wp_ajax/doing_wp_cron + referer (spec 10 §10.4).
 */
final class Request
{
    private function __construct()
    {
    }

    public static function referer(): string
    {
        $value = $_SERVER['HTTP_REFERER'] ?? '';

        return is_string($value) ? $value : '';
    }

    public static function isAdmin(): bool
    {
        if (isset($_GET['page'])) {
            return true;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (is_string($uri) && str_contains($uri, '/wp-admin')) {
            return true;
        }

        return (bool) Config::get('is_admin_request', false);
    }

    public static function doingAjax(): bool
    {
        return (isset($_POST['action']) && is_string($_POST['action']) && str_contains($_POST['action'], '-'))
            || (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], 'admin-ajax.php'));
    }

    public static function doingCron(): bool
    {
        $value = $_SERVER['REQUEST_URI'] ?? '';

        return is_string($value) && str_contains($value, 'wp-cron.php');
    }

    public static function method(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        return is_string($method) ? strtoupper($method) : 'GET';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $_REQUEST[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }
}