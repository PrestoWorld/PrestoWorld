<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Rest;

use PrestoWorld\Core\RestController;

/**
 * RestServer — replaces WP_REST_Server.
 */
class RestServer
{
    private static ?self $instance = null;

    /** @var array<string, mixed> */
    public array $routes = [];

    private function __construct()
    {
    }

    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function register_route(string $namespace, string $route, array $args = []): bool
    {
        $this->routes[$this->key($namespace, $route)] = $args;

        return RestController::register($namespace, $route, $args);
    }

    /**
     * @return array<string, mixed>
     */
    public function get_routes(): array
    {
        return RestController::server();
    }

    /**
     * @return list<string>
     */
    public function get_namespaces(): array
    {
        $serverRoutes = $this->get_routes();
        $namespaces = [];

        foreach ($serverRoutes as $key => $_) {
            $parts = explode('/', (string) $key);
            if (isset($parts[0]) && $parts[0] !== '') {
                $namespaces[] = $parts[0];
            }
        }

        $namespaces = array_values(array_unique($namespaces));

        return $namespaces;
    }

    public function dispatch(mixed $request): mixed
    {
        return RestController::dispatch($request);
    }

    public function dispatch_request(mixed $request): mixed
    {
        return $this->dispatch($request);
    }

    public function respond_to_request(mixed $request): mixed
    {
        return RestController::filterContext($request);
    }

    public static function reset(): void
    {
        self::$instance = null;
        RestController::reset();
    }

    private function key(string $namespace, string $route): string
    {
        return trim($namespace, '/') . '/' . ltrim($route, '/');
    }
}
