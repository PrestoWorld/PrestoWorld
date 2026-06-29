<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Database;

use Cycle\Database\DatabaseInterface;

class SchemaVerifier
{
    private const CACHE_KEY = 'db_schema_verified';
    private const CACHE_TTL = 3600;

    private const REQUIRED_PREFIXED_TABLES = [
        'posts',
        'terms',
        'term_relationships',
        'icl_translations',
        'post_translations',
    ];

    private const REQUIRED_GLOBAL_TABLES = [
        'schema_registry',
        'pw_migrations',
        'plugin_registry',
        'auth_tokens',
    ];

    public function __construct(
        private DatabaseInterface $db,
        private string $prefix,
        private ?string $storagePath = null,
    ) {}

    /** @return array{healthy: bool, total: int, existing: int, missing: string[], tables: array<int, array{name: string, exists: bool}>, timestamp: int} */
    public function verify(bool $forceFresh = false): array
    {
        $cacheFile = $this->getCacheFile();

        if (!$forceFresh && $cacheFile !== null && file_exists($cacheFile)) {
            $raw = file_get_contents($cacheFile);
            if ($raw !== false) {
                $cached = json_decode($raw, true);
                if (is_array($cached) && isset($cached['healthy'], $cached['total'], $cached['existing'], $cached['missing'], $cached['tables'], $cached['timestamp']) && is_bool($cached['healthy']) && is_int($cached['total']) && is_int($cached['existing']) && is_int($cached['timestamp']) && is_array($cached['missing']) && is_array($cached['tables']) && (time() - $cached['timestamp']) < self::CACHE_TTL) {
                    /** @var array{healthy: bool, total: int, existing: int, missing: string[], tables: array<int, array{name: string, exists: bool}>, timestamp: int} $verified */
                    $verified = $cached;
                    return $verified;
                }
            }
        }

        $results = $this->checkTables();

        if ($cacheFile !== null) {
            $dir = dirname($cacheFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents($cacheFile, json_encode($results));
        }

        return $results;
    }

    /** @return array{healthy: bool, total: int, existing: int, missing: string[], tables: array<int, array{name: string, exists: bool}>, timestamp: int} */
    private function checkTables(): array
    {
        $tables = [];
        $missing = [];

        foreach (self::REQUIRED_PREFIXED_TABLES as $table) {
            $name = $this->prefix . $table;
            $exists = $this->db->hasTable($name);
            $tables[] = ['name' => $name, 'exists' => $exists];
            if (!$exists) {
                $missing[] = $name;
            }
        }

        foreach (self::REQUIRED_GLOBAL_TABLES as $table) {
            $exists = $this->db->hasTable($table);
            $tables[] = ['name' => $table, 'exists' => $exists];
            if (!$exists) {
                $missing[] = $table;
            }
        }

        return [
            'healthy' => $missing === [],
            'total' => count($tables),
            'existing' => count($tables) - count($missing),
            'missing' => $missing,
            'tables' => $tables,
            'timestamp' => time(),
        ];
    }

    /** @return string[] */
    public function getRequiredTables(): array
    {
        $tables = [];
        foreach (self::REQUIRED_PREFIXED_TABLES as $table) {
            $tables[] = $this->prefix . $table;
        }
        return array_merge($tables, self::REQUIRED_GLOBAL_TABLES);
    }

    private function getCacheFile(): ?string
    {
        if ($this->storagePath === null) {
            return null;
        }
        return rtrim($this->storagePath, '/') . '/framework/cache/' . self::CACHE_KEY . '.json';
    }

}
