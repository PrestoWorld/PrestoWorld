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

    /** @var array<string, mixed> */
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
        $connection = is_string($connection) ? $connection : 'pgsql';

        if ($connection === 'mysql') {
            $database = $this->get('DB_MYSQL_DATABASE', '');
            $host = $this->get('DB_MYSQL_HOST', '127.0.0.1');
            $port = $this->get('DB_MYSQL_PORT', 3306);
            $username = $this->get('DB_MYSQL_USERNAME', 'root');
            $password = $this->get('DB_MYSQL_PASSWORD', '');
            return [
                'connection' => 'mysql',
                'database' => is_string($database) ? $database : '',
                'host' => is_string($host) ? $host : '127.0.0.1',
                'port' => is_numeric($port) ? (int) $port : 3306,
                'username' => is_string($username) ? $username : 'root',
                'password' => is_string($password) ? $password : '',
            ];
        }

        $database = $this->get('DB_PGSQL_DATABASE', '');
        $host = $this->get('DB_PGSQL_HOST', '127.0.0.1');
        $port = $this->get('DB_PGSQL_PORT', 5432);
        $username = $this->get('DB_PGSQL_USERNAME', '');
        $password = $this->get('DB_PGSQL_PASSWORD', '');
        return [
            'connection' => 'pgsql',
            'database' => is_string($database) ? $database : '',
            'host' => is_string($host) ? $host : '127.0.0.1',
            'port' => is_numeric($port) ? (int) $port : 5432,
            'username' => is_string($username) ? $username : '',
            'password' => is_string($password) ? $password : '',
        ];
    }

    /**
     * Get theme config
     *
     * @return array{active: string, path: string, url: string}
     */
    public function getThemeConfig(): array
    {
        $activeDefault = $this->get('PW_THEME_ACTIVE', 'timeless');
        $activeDefault = is_string($activeDefault) ? $activeDefault : 'timeless';
        $active = $this->get('PW_ACTIVE_THEME', $activeDefault);
        $active = is_string($active) ? $active : $activeDefault;

        $path = $this->get('PW_THEME_DIR', '');
        $path = is_string($path) ? $path : '';

        if ($path === '') {
            $contentDir = $this->get('PW_CONTENT_DIR', '');
            $contentDir = is_string($contentDir) ? $contentDir : '';
            if ($contentDir !== '') {
                $path = $contentDir . '/themes/' . $active;
            }
        }

        $contentUrl = $this->get('PW_CONTENT_URL', '/content');
        $contentUrl = is_string($contentUrl) ? $contentUrl : '/content';

        return [
            'active' => $active,
            'path' => $path,
            'url' => $contentUrl . '/themes/' . $active,
        ];
    }

    /**
     * Get table prefix
     */
    public function getTablePrefix(): string
    {
        $prefix = $this->get('PW_TABLE_PREFIX', 'pw_');
        return is_string($prefix) ? $prefix : 'pw_';
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
