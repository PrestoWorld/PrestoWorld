<?php

declare(strict_types=1);

namespace App\Config\Readers;

use App\Contracts\Config\ConfigReaderInterface;

/**
 * Environment Config Reader
 *
 * Reads configuration from environment variables.
 * This is the default reader for PrestoWorld.
 *
 * Priority: 10 (lowest — checked last)
 */
class EnvConfigReader implements ConfigReaderInterface
{
    /** @var array<string, mixed> */
    private array $cache = [];

    public function supports(): bool
    {
        // Always supports — this is the fallback reader
        return true;
    }

    public function getPriority(): int
    {
        return 10;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }

        $this->cache[$key] = $value;

        return $value;
    }

    public function has(string $key): bool
    {
        return getenv($key) !== false
            || isset($_ENV[$key])
            || isset($_SERVER[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $config = [];

        // Merge from all sources
        $sources = [$_ENV, $_SERVER];
        foreach ($sources as $source) {
            foreach ($source as $key => $value) {
                if (is_string($key) && $key !== '') {
                    $config[$key] = $value;
                }
            }
        }

        // Add getenv values
        foreach ($this->getKnownKeys() as $key) {
            $value = getenv($key);
            if ($value !== false) {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    public function getName(): string
    {
        return 'env';
    }

    /**
     * Get known configuration keys
     *
     * @return list<string>
     */
    private function getKnownKeys(): array
    {
        return [
            'APP_NAME',
            'APP_ENV',
            'APP_KEY',
            'APP_DEBUG',
            'APP_URL',
            'DB_CONNECTION',
            'DB_PGSQL_DATABASE',
            'DB_PGSQL_HOST',
            'DB_PGSQL_PORT',
            'DB_PGSQL_USERNAME',
            'DB_PGSQL_PASSWORD',
            'DB_MYSQL_DATABASE',
            'DB_MYSQL_HOST',
            'DB_MYSQL_PORT',
            'DB_MYSQL_USERNAME',
            'DB_MYSQL_PASSWORD',
            'CACHE_DRIVER',
            'QUEUE_CONNECTION',
            'ADMIN_AUTH_ENABLED',
            'PW_DEBUG',
            'PW_ACTIVE_THEME',
            'PW_THEME_ACTIVE',
            'PW_TABLE_PREFIX',
            'PW_CONTENT_DIR',
            'PW_CONTENT_URL',
            'PW_THEME_DIR',
        ];
    }
}
