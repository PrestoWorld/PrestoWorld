<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Recovery;

/**
 * FatalHandler — replaces WP_Fatal_Error_Handler.
 */
class FatalHandler
{
    /** @var array<string, mixed>|null */
    private static ?array $last_error = null;

    private static bool $registered = false;

    private string $crash_file = '';

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $file = $args['crash_file'] ?? null;
        if (is_string($file)) {
            $this->crash_file = $file;
        }
    }

    public function register(): void
    {
        self::$registered = true;
    }

    public function handle(): void
    {
        if (!self::$registered || self::$last_error === null) {
            return;
        }

        if (isset(self::$last_error['file']) && is_string(self::$last_error['file'])) {
            $this->crash_file = self::$last_error['file'];
        }
    }

    public function handle_error(
        int $error_level = 0,
        string $error_message = '',
        string $error_file = '',
        int $error_line = 0
    ): bool {
        self::$last_error = [
            'type' => $error_level,
            'message' => $error_message,
            'file' => $error_file,
            'line' => $error_line,
        ];

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get_last_error(): ?array
    {
        return self::$last_error;
    }

    public function has_last_error(): bool
    {
        return self::$last_error !== null;
    }

    public function get_crash_file(): string
    {
        return $this->crash_file;
    }

    public function set_crash_file(string $file): void
    {
        $this->crash_file = $file;
    }

    public static function reset(): void
    {
        self::$last_error = null;
        self::$registered = false;
    }
}
