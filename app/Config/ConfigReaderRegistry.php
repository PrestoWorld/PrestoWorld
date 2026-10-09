<?php

declare(strict_types=1);

namespace App\Config;

use App\Contracts\Config\ConfigReaderInterface;
use App\Config\Readers\EnvConfigReader;
use App\Config\Readers\WordPressConfigReader;

/**
 * Config Reader Registry
 *
 * Manages multiple config readers with priority-based resolution.
 * Readers are checked in priority order (highest first).
 *
 * Default readers:
 * 1. WordPressConfigReader (priority 100) — if wp-config.php exists
 * 2. EnvConfigReader (priority 10) — fallback to environment variables
 */
class ConfigReaderRegistry
{
    /** @var array<string, ConfigReaderInterface> */
    private array $readers = [];

    private ?ConfigReaderInterface $activeReader = null;

    public function __construct(string $basePath)
    {
        // Register default readers
        $this->register(new WordPressConfigReader($basePath));
        $this->register(new EnvConfigReader());
    }

    /**
     * Register a config reader
     */
    public function register(ConfigReaderInterface $reader): void
    {
        $this->readers[$reader->getName()] = $reader;
        $this->activeReader = null; // Reset active reader
    }

    /**
     * Unregister a config reader
     */
    public function unregister(string $name): void
    {
        unset($this->readers[$name]);
        $this->activeReader = null;
    }

    /**
     * Get a config reader by name
     */
    public function getReader(string $name): ?ConfigReaderInterface
    {
        return $this->readers[$name] ?? null;
    }

    /**
     * Get all registered readers
     *
     * @return array<string, ConfigReaderInterface>
     */
    public function getReaders(): array
    {
        return $this->readers;
    }

    /**
     * Get the active config reader (highest priority that supports)
     */
    public function getActiveReader(): ConfigReaderInterface
    {
        if ($this->activeReader !== null) {
            return $this->activeReader;
        }

        $readers = $this->getSortedReaders();

        foreach ($readers as $reader) {
            if ($reader->supports()) {
                $this->activeReader = $reader;
                return $reader;
            }
        }

        // Fallback to env reader (always supports)
        $envReader = $this->readers['env'] ?? new EnvConfigReader();
        $this->activeReader = $envReader;

        return $envReader;
    }

    /**
     * Get readers sorted by priority (highest first)
     *
     * @return list<ConfigReaderInterface>
     */
    public function getSortedReaders(): array
    {
        $readers = array_values($this->readers);

        usort($readers, fn (ConfigReaderInterface $a, ConfigReaderInterface $b): int => $b->getPriority() <=> $a->getPriority());

        return $readers;
    }

    /**
     * Read a configuration value
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $reader = $this->getActiveReader();

        return $reader->get($key, $default);
    }

    /**
     * Check if a configuration key exists
     */
    public function has(string $key): bool
    {
        $reader = $this->getActiveReader();

        return $reader->has($key);
    }

    /**
     * Get all configuration values from active reader
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $reader = $this->getActiveReader();

        return $reader->all();
    }

    /**
     * Get the name of the active reader
     */
    public function getActiveReaderName(): string
    {
        return $this->getActiveReader()->getName();
    }

    /**
     * Check if WordPress config is being used
     */
    public function isWordPressConfig(): bool
    {
        return $this->getActiveReaderName() === 'wordpress';
    }

    /**
     * Check if environment config is being used
     */
    public function isEnvConfig(): bool
    {
        return $this->getActiveReaderName() === 'env';
    }

    /**
     * Get diagnostic info about config readers
     *
     * @return array{active: string, readers: array<string, array{name: string, priority: int, supports: bool}>}
     */
    public function getDiagnostics(): array
    {
        $readers = [];

        foreach ($this->getSortedReaders() as $reader) {
            $readers[$reader->getName()] = [
                'name' => $reader->getName(),
                'priority' => $reader->getPriority(),
                'supports' => $reader->supports(),
            ];
        }

        return [
            'active' => $this->getActiveReaderName(),
            'readers' => $readers,
        ];
    }
}
