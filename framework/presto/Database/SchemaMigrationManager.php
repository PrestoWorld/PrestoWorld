<?php

declare(strict_types=1);

namespace PrestoWorld\Database;

use Cycle\Database\DatabaseInterface;

class SchemaMigrationManager
{
    private DatabaseInterface $db;
    private string $migrationsPath;
    private string $tablePrefix;

    private const MIGRATIONS_TABLE = 'pw_migrations';

    public function __construct(DatabaseInterface $db, string $basePath)
    {
        $this->db = $db;
        $this->migrationsPath = $basePath . '/database/migrations';
        $this->tablePrefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
    }

    public function ensureMigrationsTable(): void
    {
        if ($this->db->hasTable(self::MIGRATIONS_TABLE)) {
            return;
        }

        $schema = $this->db->table(self::MIGRATIONS_TABLE)->getSchema();
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
            ->from(self::MIGRATIONS_TABLE)
            ->orderBy('id', 'ASC')
            ->fetchAll();

        return array_map(fn(array $row) => $row['migration'], $rows);
    }

    public function markExecuted(string $name, int $batch): void
    {
        $this->db->insert(self::MIGRATIONS_TABLE)->values([
            'migration' => $name,
            'batch' => $batch,
        ])->run();
    }

    public function markRemoved(string $name): void
    {
        $this->db->delete(self::MIGRATIONS_TABLE)
            ->where('migration', $name)
            ->run();
    }

    public function runMigrations(bool $force = false): array
    {
        $this->ensureMigrationsTable();
        $executed = $this->getExecutedMigrations();

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
            return ['created' => [], 'skipped' => []];
        }

        $files = glob($this->migrationsPath . '/*.php');
        sort($files);

        $batch = $this->getNextBatch();
        $created = [];
        $skipped = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');

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

        $lastBatch = $this->db->select('MAX(batch) as batch')
            ->from(self::MIGRATIONS_TABLE)
            ->fetch();

        $batch = (int) ($lastBatch['batch'] ?? 0);
        if ($batch === 0) {
            return [];
        }

        $rolledBack = [];
        $rows = $this->db->select('migration')
            ->from(self::MIGRATIONS_TABLE)
            ->where('batch', $batch)
            ->orderBy('id', 'DESC')
            ->fetchAll();

        $names = array_map(fn(array $row) => $row['migration'], $rows);

        foreach ($names as $name) {
            $file = $this->migrationsPath . '/' . $name . '.php';
            if (file_exists($file)) {
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
        $lastBatch = $this->db->select('MAX(batch) as batch')
            ->from(self::MIGRATIONS_TABLE)
            ->fetch();

        return ((int) ($lastBatch['batch'] ?? 0)) + 1;
    }
}
