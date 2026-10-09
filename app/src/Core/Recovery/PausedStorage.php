<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Recovery;

/**
 * PausedStorage — replaces WP_Paused_Extensions_Storage.
 */
class PausedStorage
{
    /** @var array<string, array<string, array<string, mixed>>> */
    private static array $paused = [];

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $paused = $args['paused'] ?? [];
        if (!is_array($paused)) {
            return;
        }

        foreach ($paused as $type => $extensions) {
            if (!is_string($type) || !is_array($extensions)) {
                continue;
            }

            foreach ($extensions as $name => $value) {
                if (!is_string($name) || !is_array($value)) {
                    continue;
                }

                /** @var array<string, mixed> $value */
                self::$paused[$type][$name] = $value;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function get_extension_paused(string $type, string $name): array
    {
        $value = self::$paused[$type][$name] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string, mixed> $value
     */
    public function set_extension_paused(string $type, string $name, array $value): void
    {
        self::$paused[$type][$name] = $value;
    }

    public function is_extension_paused(string $type, string $name): bool
    {
        return isset(self::$paused[$type][$name]);
    }

    public function remove_extension(string $name): void
    {
        foreach (array_keys(self::$paused) as $type) {
            unset(self::$paused[$type][$name]);
        }
    }

    public function remove_all_extensions(): void
    {
        self::$paused = [];
    }

    /**
     * @return array<string, mixed>
     */
    public function get_all_paused_extensions(): array
    {
        return self::$paused;
    }

    public static function reset(): void
    {
        self::$paused = [];
    }
}
