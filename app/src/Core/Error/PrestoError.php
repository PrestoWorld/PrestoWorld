<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Error;

/**
 * PrestoError — WP_Error-compatible (spec 10 §10.5).
 */
class PrestoError
{
    /** @var array<string, array{messages: list<string>, data: list<mixed>}> */
    private array $errors = [];

    /** @var array<string, list<string>> */
    private array $codes = [];

    /**
     * @param array<int, mixed> $data
     */
    public function __construct(string $code = '', string $message = '', mixed $data = null)
    {
        if ($code !== '') {
            $this->add($code, $message, $data);
        }
    }

    public function add(string $code, string $message, mixed $data = null): void
    {
        $this->errors[$code] = [
            'messages' => array_merge($this->errors[$code]['messages'] ?? [], [$message]),
            'data' => array_merge($this->errors[$code]['data'] ?? [], [$data]),
        ];
        $this->codes[$code] = $this->errors[$code]['messages'];
    }

    public function get_error_code(): string
    {
        return array_key_first($this->codes) ?? '';
    }

    /** @return list<string> */
    public function get_error_codes(): array
    {
        return array_keys($this->codes);
    }

    public function get_error_message(?string $code = null): string
    {
        $code ??= $this->get_error_code();
        $messages = $this->codes[$code] ?? [];

        return $messages[0] ?? '';
    }

    /** @return list<string> */
    public function get_error_messages(string $code = ''): array
    {
        if ($code === '') {
            $all = [];
            foreach ($this->codes as $messages) {
                $all = array_merge($all, $messages);
            }

            return $all;
        }

        return $this->codes[$code] ?? [];
    }

    public function get_error_data(string $code = ''): mixed
    {
        $code = $code !== '' ? $code : $this->get_error_code();

        return $this->errors[$code]['data'][0] ?? null;
    }

    public function has_errors(): bool
    {
        return $this->codes !== [];
    }

    public function has(string $code): bool
    {
        return isset($this->codes[$code]);
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->codes;
    }
}