<?php

declare(strict_types=1);

namespace App\Http\Routing;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class Router
{
    protected Application $app;
    protected array $routes = [];
    protected ?\Closure $wordPressFallback = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function get(string $path, $action): Route
    {
        return $this->addRoute('GET', $path, $action);
    }

    public function post(string $path, $action): Route
    {
        return $this->addRoute('POST', $path, $action);
    }

    protected function addRoute(string $method, string $path, $action): Route
    {
        $route = new Route($method, $path, $action);
        $this->routes[] = $route;
        return $route;
    }

    public function setWordPressFallback(callable $fallback): void
    {
        $this->wordPressFallback = $fallback;
    }

    public function dispatch(Request $request): mixed
    {
        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                return $this->runRoute($route, $request);
            }
        }

        // If no native route matches, try WordPress rewrite rules
        if ($this->wordPressFallback) {
            return ($this->wordPressFallback)($request);
        }

        return Response::json(['error' => 'Not Found'], 404);
    }

    protected function runRoute(Route $route, Request $request): mixed
    {
        $action = $route->getAction();

        if ($action instanceof \Closure) {
            return $this->app->call($action, array_merge(['request' => $request], $route->getParameters()));
        }

        if (is_array($action)) {
            [$controller, $method] = $action;
            if (is_string($controller)) {
                $controller = $this->app->make($controller);
            }
            return $this->app->call([$controller, $method], array_merge(['request' => $request], $route->getParameters()));
        }

        return $action;
    }
}
