<?php

declare(strict_types=1);

namespace App\Config;

use App\Contracts\Config\ConfigReaderInterface;

/**
 * Config Manager
 *
 * Central configuration manager that uses ConfigReaderRegistry
 * to read configuration from multiple sources.
 *
 * Usage:
 *   $config = new ConfigManager($basePath);
 *   $value = $config->get('DB_PGSQL_DATABASE', 'default');
 *   $isWp = $config->isWordPressConfig();
 */
class ConfigManager
{
    private ConfigReaderRegistry $registry;

    private array $cache = [];

    public function __construct(string $basePath)
    {
        $this->registry = new ConfigReaderRegistry($basePath);
    }

    /**
     * Get a configuration value
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $value = $this->registry->get($key, $default);
        $this->cache[$key] = $value;

        return $value;
    }

    /**
     * Check if a configuration key exists
     */
    public function has(string $key): bool
    {
        return $this->registry->has($key);
    }

    /**
     * Get all configuration values
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->registry->all();
    }

    /**
     * Get the active config reader
     */
    public function getActiveReader(): ConfigReaderInterface
    {
        return $this->registry->getActiveReader();
    }

    /**
     * Get the active reader name
     */
    public function getActiveReaderName(): string
    {
        return $this->registry->getActiveReaderName();
    }

    /**
     * Check if WordPress config is being used
     */
    public function isWordPressConfig(): bool
    {
        return $this->registry->isWordPressConfig();
    }

    /**
     * Check if environment config is being used
     */
    public function isEnvConfig(): bool
    {
        return $this->registry->isEnvConfig();
    }

    /**
     * Get the config reader registry
     */
    public function getRegistry(): ConfigReaderRegistry
    {
        return $this->registry;
    }

    /**
     * Register a custom config reader
     */
    public function registerReader(ConfigReaderInterface $reader): void
    {
        $this->registry->register($reader);
        $this->cache = []; // Clear cache
    }

    /**
     * Clear configuration cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Get diagnostic info
     *
     * @return array{active: string, readers: array<string, array{name: string, priority: int, supports: bool}>}
     */
    public function getDiagnostics(): array
    {
        return $this->registry->getDiagnostics();
    }

    /**
     * Get database connection config
     *
     * @return array{connection: string, database: string, host: string, port: int, username: string, password: string}
     */
    public function getDatabaseConfig(): array
    {
        $connection = $this->get('DB_CONNECTION', 'pgsql');

        if ($connection === 'mysql') {
            return [
                'connection' => 'mysql',
                'database' => $this->get('DB_MYSQL_DATABASE', ''),
                'host' => $this->get('DB_MYSQL_HOST', '127.0.0.1'),
                'port' => (int) $this->get('DB_MYSQL_PORT', 3306),
                'username' => $this->get('DB_MYSQL_USERNAME', 'root'),
                'password' => $this->get('DB_MYSQL_PASSWORD', ''),
            ];
        }

        return [
            'connection' => 'pgsql',
            'database' => $this->get('DB_PGSQL_DATABASE', ''),
            'host' => $this->get('DB_PGSQL_HOST', '127.0.0.1'),
            'port' => (int) $this->get('DB_PGSQL_PORT', 5432),
            'username' => $this->get('DB_PGSQL_USERNAME', ''),
            'password' => $this->get('DB_PGSQL_PASSWORD', ''),
        ];
    }

    /**
     * Get theme config
     *
     * @return array{active: string, path: string, url: string}
     */
    public function getThemeConfig(): array
    {
        $active = $this->get('PW_ACTIVE_THEME', $this->get('PW_THEME_ACTIVE', 'jankx'));
        $path = $this->get('PW_THEME_DIR', '');

        if ($path === '') {
            $contentDir = $this->get('PW_CONTENT_DIR', '');
            if ($contentDir !== '') {
                $path = $contentDir . '/themes/' . $active;
            }
        }

        return [
            'active' => $active,
            'path' => $path,
            'url' => $this->get('PW_CONTENT_URL', '/content') . '/themes/' . $active,
        ];
    }

    /**
     * Get table prefix
     */
    public function getTablePrefix(): string
    {
        return $this->get('PW_TABLE_PREFIX', 'pw_');
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebug(): bool
    {
        $debug = $this->get('PW_DEBUG', $this->get('APP_DEBUG', false));

        return $debug === true || $debug === 'true' || $debug === '1';
    }
}
