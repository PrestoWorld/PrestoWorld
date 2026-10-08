<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Kses + meta/request nhẹ (stub đủ cho shim).
 */
final class JsonResponse
{
    private function __construct()
    {
    }

    public static function send(mixed $response, int $statusCode = 200): never
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, ?int $statusCode = null): never
    {
        self::send(['success' => true, 'data' => $data], $statusCode ?? 200);
    }

    public static function error(mixed $data = null, ?int $statusCode = null): never
    {
        self::send(['success' => false, 'data' => $data], $statusCode ?? 400);
    }
}