<?php

declare(strict_types=1);

namespace App\Contracts\Config;

/**
 * Config Reader Interface
 *
 * Defines the contract for configuration readers.
 * Implementations can read from environment variables,
 * WordPress config files, database, etc.
 */
interface ConfigReaderInterface
{
    /**
     * Check if this reader can handle the current environment
     */
    public function supports(): bool;

    /**
     * Get configuration priority (higher = checked first)
     */
    public function getPriority(): int;

    /**
     * Read a configuration value
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null);

    /**
     * Check if a configuration key exists
     */
    public function has(string $key): bool;

    /**
     * Get all configuration values
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Get the reader name
     */
    public function getName(): string;
}
