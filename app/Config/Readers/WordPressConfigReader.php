<?php

declare(strict_types=1);

namespace App\Config\Readers;

use App\Contracts\Config\ConfigReaderInterface;

/**
 * WordPress Config Reader
 *
 * Reads configuration from wp-config.php when it exists
 * in the root directory (alongside composer.json and config/).
 *
 * Priority: 100 (highest — checked first)
 *
 * Detection criteria:
 * - wp-config.php exists in root directory
 * - Root directory also contains composer.json
 * - Root directory also contains config/ directory
 */
class WordPressConfigReader implements ConfigReaderInterface
{
    private ?string $configPath = null;

    private ?array $config = null;

    private ?bool $supported = null;

    public function __construct(private string $basePath)
    {
        $this->configPath = $this->detectConfigPath();
    }

    public function supports(): bool
    {
        if ($this->supported !== null) {
            return $this->supported;
        }

        $this->supported = $this->configPath !== null
            && $this->hasRequiredFiles();

        return $this->supported;
    }

    public function getPriority(): int
    {
        return 100;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->supports()) {
            return $default;
        }

        $config = $this->loadConfig();

        return $config[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        if (!$this->supports()) {
            return false;
        }

        $config = $this->loadConfig();

        return isset($config[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (!$this->supports()) {
            return [];
        }

        return $this->loadConfig();
    }

    public function getName(): string
    {
        return 'wordpress';
    }

    /**
     * Get the detected wp-config.php path
     */
    public function getConfigPath(): ?string
    {
        return $this->configPath;
    }

    /**
     * Detect wp-config.php path
     */
    private function detectConfigPath(): ?string
    {
        $candidates = [
            $this->basePath . '/wp-config.php',
            $this->basePath . '/public/wp-config.php',
            dirname($this->basePath) . '/wp-config.php',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Check if required files exist (composer.json, config/)
     */
    private function hasRequiredFiles(): bool
    {
        if ($this->configPath === null) {
            return false;
        }

        $rootDir = dirname($this->configPath);

        // Must have composer.json
        if (!file_exists($rootDir . '/composer.json')) {
            return false;
        }

        // Must have config directory
        if (!is_dir($rootDir . '/config')) {
            return false;
        }

        return true;
    }

    /**
     * Load and parse wp-config.php
     *
     * @return array<string, mixed>
     */
    private function loadConfig(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $this->config = [];

        if ($this->configPath === null || !file_exists($this->configPath)) {
            return $this->config;
        }

        $raw = file_get_contents($this->configPath);
        if ($raw === false || $raw === '') {
            return $this->config;
        }

        // Parse define() statements
        $pattern = '/define\s*\(\s*[\'"](?<key>[A-Z0-9_]+)[\'"]\s*,\s*(?<value>\'[^\']*(?:\\\.[^\']*)*\'|"[^"]*(?:\\\.[^"]*)*"|true|false|[0-9.]+)\s*\)\s*;/i';

        if (preg_match_all($pattern, $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match['key'];
                $valueRaw = $match['value'];
                $this->config[$key] = $this->parseValue($valueRaw);
            }
        }

        // Parse $table_prefix
        if (preg_match('/\$table_prefix\s*=\s*[\'"](?<prefix>[a-zA-Z0-9_]+)[\'"]\s*;/', $raw, $m)) {
            $this->config['WP_TABLE_PREFIX'] = $m['prefix'];
        }

        // Detect WP_CONTENT_DIR
        $dir = dirname($this->configPath);
        $wpContent = $dir . '/wp-content';
        if (is_dir($wpContent)) {
            $this->config['WP_CONTENT_DIR'] = realpath($wpContent);
        }

        // Fallback to content/ directory if wp-content doesn't exist
        $contentDir = $dir . '/content';
        if (!isset($this->config['WP_CONTENT_DIR']) && is_dir($contentDir)) {
            $this->config['WP_CONTENT_DIR'] = realpath($contentDir);
        }

        // Map WordPress config to PrestoWorld config
        $this->config = $this->mapToPrestoWorld($this->config);

        return $this->config;
    }

    /**
     * Parse a value from wp-config.php
     */
    private function parseValue(string $value): mixed
    {
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }

        if ((str_starts_with($value, "'") && str_ends_with($value, "'")) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
        ) {
            return substr($value, 1, -1);
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }

    /**
     * Map WordPress config keys to PrestoWorld config keys
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function mapToPrestoWorld(array $config): array
    {
        $mapped = $config;

        // Map database config
        if (isset($config['DB_NAME'])) {
            $mapped['DB_PGSQL_DATABASE'] = $config['DB_NAME'];
            $mapped['DB_MYSQL_DATABASE'] = $config['DB_NAME'];
        }

        if (isset($config['DB_USER'])) {
            $mapped['DB_PGSQL_USERNAME'] = $config['DB_USER'];
            $mapped['DB_MYSQL_USERNAME'] = $config['DB_USER'];
        }

        if (isset($config['DB_PASSWORD'])) {
            $mapped['DB_PGSQL_PASSWORD'] = $config['DB_PASSWORD'];
            $mapped['DB_MYSQL_PASSWORD'] = $config['DB_PASSWORD'];
        }

        if (isset($config['DB_HOST'])) {
            $mapped['DB_PGSQL_HOST'] = $config['DB_HOST'];
            $mapped['DB_MYSQL_HOST'] = $config['DB_HOST'];
        }

        if (isset($config['DB_CHARSET'])) {
            $mapped['DB_CHARSET'] = $config['DB_CHARSET'];
        }

        // Map table prefix
        if (isset($config['WP_TABLE_PREFIX'])) {
            $mapped['PW_TABLE_PREFIX'] = $config['WP_TABLE_PREFIX'];
        }

        // Map content directory
        if (isset($config['WP_CONTENT_DIR'])) {
            $mapped['PW_CONTENT_DIR'] = $config['WP_CONTENT_DIR'];
            $mapped['PW_CONTENT_URL'] = '/' . basename($config['WP_CONTENT_DIR']);
        }

        // Detect theme
        $contentDir = $config['WP_CONTENT_DIR'] ?? null;
        if ($contentDir !== null && is_dir($contentDir . '/themes')) {
            $themesDir = $contentDir . '/themes';
            $theme = $this->detectActiveTheme($themesDir);
            if ($theme !== null) {
                $mapped['PW_ACTIVE_THEME'] = $theme;
                $mapped['PW_THEME_ACTIVE'] = $theme;
                $mapped['PW_THEME_DIR'] = $themesDir . '/' . $theme;
            }
        }

        // Preserve PrestoWorld-specific env vars if already set
        // This allows .env to override WordPress config
        $envVars = ['PW_ACTIVE_THEME', 'PW_THEME_ACTIVE', 'PW_THEME_DIR', 'PW_TABLE_PREFIX', 'PW_CONTENT_DIR', 'PW_CONTENT_URL'];
        foreach ($envVars as $envVar) {
            // Check $_ENV first (from .env file), then getenv
            $envValue = $_ENV[$envVar] ?? $_SERVER[$envVar] ?? getenv($envVar);
            if ($envValue !== false && $envValue !== '' && $envValue !== null) {
                $mapped[$envVar] = $envValue;
            }
        }

        return $mapped;
    }

    /**
     * Detect active theme from themes directory
     */
    private function detectActiveTheme(string $themesDir): ?string
    {
        $entries = scandir($themesDir);
        if ($entries === false) {
            return null;
        }

        $themes = array_values(
            array_filter($entries, fn(string $d): bool => $d[0] !== '.' && is_dir("{$themesDir}/{$d}"))
        );

        if (empty($themes)) {
            return null;
        }

        // Prefer modern WordPress themes
        $preferred = ['twentytwentyfive', 'twentytwentyfour', 'twentytwentythree'];
        foreach ($preferred as $name) {
            if (in_array($name, $themes, true)) {
                return $name;
            }
        }

        return $themes[0];
    }
}
