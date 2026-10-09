<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * RestController — register_rest_route + REST helpers
 * (spec 10 §10.4.18 Group 17). Route table in-memory; server thật wiring sau.
 */
final class RestController
{
    /**
     * @var array<string, list<array{methods: list<string>, callback: mixed, args: array<string, mixed>}>>
     */
    private static array $routes = [];

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function register(string $namespace, string $route, array $args = []): bool
    {
        $rawMethods = $args['methods'] ?? 'GET';
        if (is_string($rawMethods)) {
            $methodList = explode(',', $rawMethods);
        } elseif (is_array($rawMethods)) {
            $methodList = $rawMethods;
        } else {
            $methodList = ['GET'];
        }

        $methods = [];
        foreach ($methodList as $method) {
            $methods[] = strtoupper(trim(is_scalar($method) ? (string) $method : ''));
        }

        self::$routes[self::key($namespace, $route)][] = [
            'methods' => $methods,
            'callback' => $args['callback'] ?? null,
            'args' => $args,
        ];

        return true;
    }

    public static function ensureResponse(mixed $response): mixed
    {
        return $response;
    }

    /**
     * @return array<string, list<array{methods: list<string>, callback: mixed, args: array<string, mixed>}>>
     */
    public static function server(): array
    {
        return self::$routes;
    }

    public static function dispatch(mixed $request): mixed
    {
        return $request;
    }

    /**
     * @param array<int, string> $context
     */
    public static function filterContext(mixed $response, array $context = []): mixed
    {
        return $response;
    }

    public static function reset(): void
    {
        self::$routes = [];
    }

    private static function key(string $namespace, string $route): string
    {
        return trim($namespace, '/') . '/' . ltrim($route, '/');
    }
}
