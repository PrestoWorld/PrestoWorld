<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * HttpService — wp_remote_get/download_url (spec 10 §10.4 Group http).
 *
 * Trả về mảng shaped như WP: {headers, body, response: {code, message}} | false.
 */
final class HttpService
{
    private function __construct()
    {
    }

    /**
     * @return array{headers: array<string, string>, body: string, response: array{code: int, message: string}, cookies: array<int, string>}|false
     */
    public static function download(string $url, array $args = []): array|false
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $timeout = (int) ($args['timeout'] ?? 10);

        $context = stream_context_create([
            'http' => [
                'method' => strtoupper((string) ($args['method'] ?? 'GET')),
                'timeout' => $timeout,
                'header' => self::buildHeaders($args),
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            return false;
        }

        $headers = [];
        foreach ($http_response_header ?? [] as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }

        $statusLine = $http_response_header[0] ?? 'HTTP/1.1 200 OK';
        preg_match('#HTTP/\S+\s(\d{3})#', $statusLine, $m);
        $code = isset($m[1]) ? (int) $m[1] : 200;

        return [
            'headers' => $headers,
            'body' => $body,
            'response' => ['code' => $code, 'message' => $code >= 400 ? 'Error' : 'OK'],
            'cookies' => [],
        ];
    }

    /**
     * @param array<string, mixed> $args
     */
    private static function buildHeaders(array $args): string
    {
        $headers = ['User-Agent: PrestoWorld/1.0'];
        foreach ((array) ($args['headers'] ?? []) as $name => $value) {
            $headers[] = is_int($name) ? (string) $value : $name . ': ' . $value;
        }

        return implode("\r\n", $headers) . "\r\n";
    }
}