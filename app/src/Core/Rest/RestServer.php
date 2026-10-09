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
        if (method_exists(RestController::class, 'register')) {
            return RestController::register($namespace, $route, $args);
        }

        $this->routes[$this->key($namespace, $route)] = $args;

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_routes(): array
    {
        return method_exists(RestController::class, 'server') ? RestController::server() : $this->routes;
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
        if (method_exists(RestController::class, 'dispatch')) {
            return RestController::dispatch($request);
        }

        if ($request instanceof RestRequest) {
            return $this->respond_to_request($request);
        }

        if (is_array($request)) {
            return $this->respond_to_request($request);
        }

        return $request;
    }

    public function dispatch_request(mixed $request): mixed
    {
        return $this->dispatch($request);
    }

    public function respond_to_request(mixed $request): mixed
    {
        if (method_exists(RestController::class, 'filterContext')) {
            return RestController::filterContext($request);
        }

        return $request;
    }

    public static function reset(): void
    {
        self::$instance = null;
        if (method_exists(RestController::class, 'reset')) {
            RestController::reset();
        }
    }

    private function key(string $namespace, string $route): string
    {
        return trim($namespace, '/') . '/' . ltrim($route, '/');
    }
}
