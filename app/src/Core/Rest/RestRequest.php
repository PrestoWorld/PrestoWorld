<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Rest;

/**
 * RestRequest — replaces WP_REST_Request.
 */
class RestRequest
{
    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var array<string, mixed> */
    private array $params = [];

    /** @var array<string, string> */
    private array $headers = [];

    /** @var array<string, mixed> */
    private array $bodyParams = [];

    public function __construct(
        private string $method = 'GET',
        private string $route = '',
        array $attributes = [],
        array $params = [],
        array $headers = [],
        private mixed $body = null
    ) {
        $this->set_attributes($attributes);
        $this->set_params($params);
        $this->set_headers($headers);
    }

    private function normalizeHeaderKey(string $key): string
    {
        return strtolower($key);
    }

    public function get_method(): string
    {
        return $this->method;
    }

    public function set_method(string $method): void
    {
        $this->method = strtoupper($method);
    }

    public function get_route(): string
    {
        return $this->route;
    }

    public function set_route(string $route): void
    {
        $this->route = $route;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_params(): array
    {
        return $this->params;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function set_params(array $params): void
    {
        $this->params = $params;
    }

    public function get_param(string $key): mixed
    {
        return $this->params[$key] ?? null;
    }

    public function set_param(string $key, mixed $value): void
    {
        $this->params[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_attributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set_attributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function get_headers(): array
    {
        return $this->headers;
    }

    /**
     * @param array<string, string> $headers
     */
    public function set_headers(array $headers): void
    {
        $this->headers = [];
        foreach ($headers as $key => $value) {
            if (is_string($key)) {
                $this->set_header($key, is_string($value) ? $value : '');
            }
        }
    }

    public function get_header(string $key): ?string
    {
        $normalized = $this->normalizeHeaderKey($key);
        if (isset($this->headers[$normalized])) {
            return $this->headers[$normalized];
        }

        // Also check original case stored? We store normalized; also look for normalized match
        foreach ($this->headers as $storedKey => $storedValue) {
            if ($this->normalizeHeaderKey($storedKey) === $normalized) {
                return $storedValue;
            }
        }

        return null;
    }

    public function set_header(string $key, string $value): void
    {
        $this->headers[$this->normalizeHeaderKey($key)] = $value;
    }

    public function get_body(): mixed
    {
        return $this->body;
    }

    public function set_body(mixed $body): void
    {
        $this->body = $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_body_params(): array
    {
        return $this->bodyParams;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function set_body_params(array $params): void
    {
        $this->bodyParams = $params;
    }

    public function has_valid_params(): bool
    {
        return true;
    }

    public function sanitize_params(): bool
    {
        return true;
    }
}
