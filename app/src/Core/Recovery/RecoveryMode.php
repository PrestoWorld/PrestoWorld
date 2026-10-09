<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Recovery;

/**
 * RecoveryMode — replaces WP_Recovery_Mode.
 */
class RecoveryMode
{
    private static bool $active = false;

    /** @var array<int, mixed> */
    private array $extension_causes = [];

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $causes = $args['extension_causes'] ?? [];
        foreach ((array) $causes as $cause) {
            $this->extension_causes[] = $cause;
        }
    }

    public function is_active(): bool
    {
        return self::$active;
    }

    public function is_active_for_network(): bool
    {
        return self::$active;
    }

    public function isRecoveryMode(): bool
    {
        return self::$active;
    }

    public function enable(): void
    {
        self::$active = true;
    }

    public function disable(): void
    {
        self::$active = false;
    }

    public function handle_error(mixed $error = null): void
    {
    }

    public function handle_critical_error(mixed $error = null): void
    {
    }

    public function is_url_authorized(string $url): bool
    {
        return false;
    }

    /**
     * @return array<int, mixed>
     */
    public function get_extension_causes(): array
    {
        return $this->extension_causes;
    }

    /**
     * @param array<int, mixed> $causes
     */
    public function set_extension_causes(array $causes): void
    {
        $this->extension_causes = $causes;
    }

    public static function reset(): void
    {
        self::$active = false;
    }
}
