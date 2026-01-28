<?php

declare(strict_types=1);

namespace App\Http\Routing;

use Closure;
use Witals\Framework\Http\Request;

class Route
{
    protected string $method;
    protected string $path;
    protected $action;
    protected array $parameters = [];

    public function __construct(string $method, string $path, $action)
    {
        $this->method = strtoupper($method);
        $this->path = '/' . ltrim($path, '/');
        $this->action = $action;
    }

    public function matches(Request $request): bool
    {
        if ($this->method !== $request->method()) {
            return false;
        }

        $pattern = $this->getRegexPattern();
        if (preg_match($pattern, $request->path(), $matches)) {
            $this->parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return true;
        }

        return false;
    }

    protected function getRegexPattern(): string
    {
        // Convert {param} to (?P<param>[^/]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $this->path);
        return '#^' . $pattern . '$#';
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
