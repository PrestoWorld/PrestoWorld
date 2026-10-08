<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Response helpers.
 */
final class Response
{
    private function __construct()
    {
    }

    public static function noCache(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    }

    public static function status(int $code): void
    {
        if (!headers_sent()) {
            http_response_code($code);
        }
    }

    public static function json(mixed $data, int $code = 200): void
    {
        self::status($code);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}