<?php

declare(strict_types=1);

namespace PrestoWorld\Database;

use Cycle\Database\DatabaseInterface;

class SchemaMigrationManager
{
    private DatabaseInterface $db;
    private string $basePath;
    private string $migrationsPath;
    /** @var list<string> */
    private array $migrationPaths;
    private string $tablePrefix;
    private string $migrationsTable;

    /**
     * @param list<string> $moduleMigrationPaths Directories owned by enabled
     *        modules. Module migrations take precedence over published copies
     *        in database/migrations.
     * @param string|null $tablePrefix Explicit table prefix. Callers that
     *        just wrote a new prefix (the installer) pass it here: getenv()
     *        still returns the value the process booted with, so reading it
     *        again would create the tables under the previous prefix.
     */
    public function __construct(
        DatabaseInterface $db,
        string $basePath,
        array $moduleMigrationPaths = [],
        ?string $tablePrefix = null,
    ) {
        $this->db = $db;
        $this->basePath = rtrim($basePath, '/');
        $this->migrationsPath = $this->basePath . '/database/migrations';
        $this->tablePrefix = $tablePrefix ?? (getenv('PW_TABLE_PREFIX') ?: 'pw_');
        $this->migrationsTable = $this->tablePrefix . 'migrations';

        $paths = array_merge([$this->migrationsPath], $moduleMigrationPaths);
        $this->migrationPaths = array_values(array_unique(array_filter(
            array_map(static fn (string $path): string => rtrim($path, '/'), $paths),
            static fn (string $path): bool => $path !== '',
        )));
    }

    /**
     * All migration files keyed by migration name, sorted by name.
     *
     * A migration file published into database/migrations is a copy of a
     * module owned file: when both exist the module wins. Two different
     * modules shipping the same file name are both kept - their ledger keys
     * are qualified with the owning module path so neither is shadowed.
     *
     * @return array<string, string>
     */
    public function migrationFiles(): array
    {
        $groups = [];

        foreach ($this->migrationPaths as $path) {
            foreach (glob($path . '/*.php') ?: [] as $file) {
                $name = basename($file, '.php');
                $groups[$name][] = ['path' => $path, 'file' => $file];
            }
        }

        $files = [];

        foreach ($groups as $name => $group) {
            $moduleOwned = array_values(array_filter(
                $group,
                fn (array $entry): bool => $entry['path'] !== $this->migrationsPath,
            ));

            // The module owned file replaces the published copy.
            if ($moduleOwned !== [] && count($group) > 1) {
                $group = $moduleOwned;
            }

            if (count($group) === 1) {
                $files[$name] = $group[0]['file'];

                continue;
            }

            foreach ($group as $entry) {
                $files[$this->qualifyMigrationName($entry['path'], $name)] = $entry['file'];
            }
        }

        ksort($files);

        return $files;
    }

    /**
     * Ledger key for a migration name that two modules share. The owning
     * module path keeps the key unique and stable across runs.
     */
    private function qualifyMigrationName(string $path, string $name): string
    {
        $relative = $path;
        if (str_starts_with($path, $this->basePath . '/')) {
            $relative = substr($path, strlen($this->basePath) + 1);
        }

        $relative = trim(str_replace(['/', '\\'], '_', $relative), '_');

        return $relative === '' ? $name : $relative . '_' . $name;
    }

    public function ensureMigrationsTable(): void
    {
        if ($this->db->hasTable($this->migrationsTable)) {
            return;
        }

        $schema = $this->db->table($this->migrationsTable)->getSchema();
        $schema->primary('id');
        $schema->column('migration')->string(255);
        $schema->column('batch')->integer();
        $schema->column('executed_at')->datetime()->defaultValue('CURRENT_TIMESTAMP');
        $schema->index(['migration'])->unique();
        $schema->save();
    }

    public function getExecutedMigrations(): array
    {
        $this->ensureMigrationsTable();

        $rows = $this->db->select('migration')
            ->from($this->migrationsTable)
            ->orderBy('id', 'ASC')
            ->fetchAll();

        return array_map(fn(array $row) => $row['migration'], $rows);
    }

    public function markExecuted(string $name, int $batch): void
    {
        $this->db->insert($this->migrationsTable)->values([
            'migration' => $name,
            'batch' => $batch,
        ])->run();
    }

    public function markRemoved(string $name): void
    {
        $this->db->delete($this->migrationsTable)
            ->where('migration', $name)
            ->run();
    }

    public function runMigrations(bool $force = false): array
    {
        $this->ensureMigrationsTable();
        $executed = $this->getExecutedMigrations();

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        $batch = $this->getNextBatch();
        $created = [];
        $skipped = [];

        foreach ($this->migrationFiles() as $name => $file) {
            if (in_array($name, $executed, true)) {
                $skipped[] = $name;
                continue;
            }

            $migration = require $file;

            if ($migration instanceof MigrationInterface) {
                $migration->up($this->db, $this->tablePrefix);
                $this->markExecuted($name, $batch);
                $created[] = $name;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function rollbackLastBatch(): array
    {
        $this->ensureMigrationsTable();

        $lastBatch = $this->db->select('batch')
            ->from($this->migrationsTable)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->run()->fetch();

        $value = is_array($lastBatch) ? ($lastBatch['batch'] ?? 0) : 0;
        $batch = is_numeric($value) ? (int) $value : 0;
        if ($batch === 0) {
            return [];
        }

        $rolledBack = [];
        $rows = $this->db->select('migration')
            ->from($this->migrationsTable)
            ->where('batch', $batch)
            ->orderBy('id', 'DESC')
            ->fetchAll();

        $names = array_map(fn(array $row) => $row['migration'], $rows);

        $files = $this->migrationFiles();

        foreach ($names as $name) {
            $file = $files[$name] ?? null;
            if ($file !== null && is_file($file)) {
                $migration = require $file;
                if ($migration instanceof MigrationInterface) {
                    $migration->down($this->db, $this->tablePrefix);
                }
            }
            $this->markRemoved($name);
            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    private function getNextBatch(): int
    {
        $lastBatch = $this->db->select('batch')
            ->from($this->migrationsTable)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->run()->fetch();

        $value = is_array($lastBatch) ? ($lastBatch['batch'] ?? 0) : 0;

        return (is_numeric($value) ? (int) $value : 0) + 1;
    }
}
