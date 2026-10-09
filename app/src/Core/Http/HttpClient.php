<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Http;

use PrestoWorld\Core\Error\PrestoError;
use PrestoWorld\Core\HttpService;

/**
 * HttpClient — replaces WP_Http.
 */
final class HttpClient
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|PrestoError
     */
    public static function request(string $url, array $args = []): array|PrestoError
    {
        $result = HttpService::download($url, $args);
        if ($result === false) {
            return new PrestoError('http_request_failed', 'Request to ' . $url . ' failed.');
        }

        $response = is_array($result['response'] ?? null) ? $result['response'] : [];
        $code = $response['code'] ?? 0;

        return [
            'headers' => is_array($result['headers'] ?? null) ? $result['headers'] : [],
            'body' => is_string($result['body'] ?? null) ? $result['body'] : '',
            'response' => [
                'code' => is_numeric($code) ? (int) $code : 0,
                'message' => is_string($response['message'] ?? null) ? $response['message'] : '',
            ],
            'cookies' => is_array($result['cookies'] ?? null) ? array_values($result['cookies']) : [],
            'filename' => null,
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|PrestoError
     */
    public static function get(string $url, array $args = []): array|PrestoError
    {
        $args['method'] = 'GET';

        return self::request($url, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|PrestoError
     */
    public static function post(string $url, array $args = []): array|PrestoError
    {
        $args['method'] = 'POST';

        return self::request($url, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|PrestoError
     */
    public static function head(string $url, array $args = []): array|PrestoError
    {
        $args['method'] = 'HEAD';

        return self::request($url, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|PrestoError
     */
    public static function put(string $url, array $args = []): array|PrestoError
    {
        $args['method'] = 'PUT';

        return self::request($url, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|PrestoError
     */
    public static function delete(string $url, array $args = []): array|PrestoError
    {
        $args['method'] = 'DELETE';

        return self::request($url, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|PrestoError
     */
    public static function patch(string $url, array $args = []): array|PrestoError
    {
        $args['method'] = 'PATCH';

        return self::request($url, $args);
    }
}
