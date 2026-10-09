<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Rest;

use JsonSerializable;

/**
 * RestResponse — replaces WP_REST_Response.
 */
class RestResponse implements JsonSerializable
{
    /** @var array<string, mixed> */
    private array $headers = [];

    /** @var array<string, array<string, string>> */
    private array $links = [];

    public function __construct(
        private mixed $data = null,
        private int $status = 200,
        array $headers = []
    ) {
        foreach ($headers as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $this->header($key, $value, true);
            }
        }
    }

    public function get_data(): mixed
    {
        return $this->data;
    }

    public function set_data(mixed $data): void
    {
        $this->data = $data;
    }

    public function get_status(): int
    {
        return $this->status;
    }

    public function set_status(int $status): void
    {
        $this->status = $status;
    }

    public function get_status_code(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_headers(): array
    {
        return $this->headers;
    }

    public function header(string $key, string $value, bool $replace = false): void
    {
        $keyLower = strtolower($key);
        if ($replace || !isset($this->headers[$keyLower])) {
            $this->headers[$keyLower] = $value;
        } else {
            $existing = $this->headers[$keyLower];
            $this->headers[$keyLower] = is_string($existing) ? $existing . ', ' . $value : $value;
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function get_links(): array
    {
        return $this->links;
    }

    public function add_link(string $rel, string $href): void
    {
        $this->links[$rel] = ['href' => $href];
    }

    public function is_error(): bool
    {
        return $this->status >= 400;
    }

    /**
     * @return array<string, mixed>
     */
    public function as_error(): array
    {
        return [
            'status' => $this->status,
            'data' => $this->data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'status' => $this->status,
            'headers' => $this->headers,
            'links' => $this->links,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }
}
