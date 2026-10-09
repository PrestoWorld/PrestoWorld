<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Http;

/**
 * Proxy — replaces WP_HTTP_Proxy.
 */
final class Proxy
{
    private bool $enabled = false;
    private string $host = '';
    private int $port = 0;
    private string $user = '';
    private string $pass = '';

    public function __construct(?string $proxy = null)
    {
        if ($proxy !== null && $proxy !== '') {
            $this->parse($proxy);
        }
    }

    public static function from_environment(): self
    {
        foreach (['HTTPS_PROXY', 'https_proxy', 'HTTP_PROXY', 'http_proxy'] as $key) {
            $value = getenv($key);
            if (is_string($value) && $value !== '') {
                return new self($value);
            }
        }

        return new self();
    }

    private function parse(string $proxy): void
    {
        $parts = parse_url($proxy);
        if (!is_array($parts)) {
            return;
        }

        $this->enabled = true;
        $this->host = is_string($parts['host'] ?? null) ? $parts['host'] : '';
        $this->port = isset($parts['port']) && is_numeric($parts['port']) ? (int) $parts['port'] : 0;
        $this->user = is_string($parts['user'] ?? null) ? $parts['user'] : '';
        $this->pass = is_string($parts['pass'] ?? null) ? $parts['pass'] : '';
    }

    public function is_enabled(): bool
    {
        return $this->enabled && $this->host !== '';
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled();
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function user(): string
    {
        return $this->user;
    }

    public function pass(): string
    {
        return $this->pass;
    }

    public function user_agent(): string
    {
        return 'PrestoWorld/1.0';
    }

    public function use_authentication(): bool
    {
        return $this->user !== '';
    }

    public function authentication(): string
    {
        return $this->use_authentication() ? $this->user . ':' . $this->pass : '';
    }

    public function get_host(): string
    {
        return $this->host;
    }

    public function get_port(): int
    {
        return $this->port;
    }
}
