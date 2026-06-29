<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Witals\Framework\Console\Command;
use Cycle\Database\Database;
use Cycle\Database\DatabaseManager;
use PrestoWorld\Foundation\Database\SchemaVerifier;

class DbInitCommand extends Command
{
    protected string $name = 'db:init';
    protected string $description = 'Initialize PrestoWorld database — create all required tables';

    /** @param string[] $args */
    public function handle(array $args): int
    {
        $force = $this->hasOption($args, 'force', 'f');
        $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';

        /** @var DatabaseManager $dm */
        $dm = $this->app->make(DatabaseManager::class);

        $this->info('Initializing PrestoWorld database...');
        $this->line("  Connection: {$dm->database()->getName()}");
        $this->line("  Prefix: {$prefix}");
        $this->line('');

        $databases = ['presto', 'wordpress'];
        $created = 0;
        $skipped = 0;

        foreach ($databases as $name) {
            $db = $dm->database($name);
            if (!$db instanceof Database) {
                continue;
            }

            $result = $this->initCoreTables($db, $prefix, $force);
            $created += $result['created'];
            $skipped += $result['skipped'];

            $result = $this->initTranslationTables($db, $prefix, $force);
            $created += $result['created'];
            $skipped += $result['skipped'];
        }

        // Global tables on the 'presto' connection
        $db = $dm->database('presto');
        if ($db instanceof Database) {
            $result = $this->initGlobalTables($db, $force);
            $created += $result['created'];
            $skipped += $result['skipped'];
        }

        $this->line('');
        $this->info("Done — {$created} created, {$skipped} skipped.");

        // Run verification
        $this->line('');
        $verifier = new SchemaVerifier($db, $prefix, $this->app->storagePath());
        $status = $verifier->verify(true);

        if ($status['healthy']) {
            $this->info("[OK] All {$status['total']} required tables exist.");
            return 0;
        }

        $this->error("[WARN] {$status['existing']}/{$status['total']} tables exist.");
        foreach ($status['missing'] as $table) {
            $this->line("  - {$table}");
        }

        return 1;
    }

    /** @return array{created: int, skipped: int} */
    private function initCoreTables(Database $db, string $prefix, bool $force): array
    {
        $tables = [
            [
                'name' => "{$prefix}posts",
                'columns' => [
                    ['type' => 'primary', 'name' => 'id'],
                    ['type' => 'string(32)', 'name' => 'post_type', 'nullable' => false],
                    ['type' => 'string(255)', 'name' => 'title', 'nullable' => false],
                    ['type' => 'string(255)', 'name' => 'slug', 'nullable' => false],
                    ['type' => 'string(20)', 'name' => 'status', 'nullable' => false, 'default' => 'publish'],
                    ['type' => 'integer', 'name' => 'author_id', 'nullable' => true],
                    ['type' => 'bigInteger', 'name' => 'trid', 'nullable' => true],
                    ['type' => 'datetime', 'name' => 'created_at', 'nullable' => false, 'default' => 'CURRENT_TIMESTAMP'],
                    ['type' => 'datetime', 'name' => 'updated_at', 'nullable' => true],
                    ['type' => 'json', 'name' => 'compact_meta', 'nullable' => true],
                ],
                'indexes' => [['post_type'], ['slug'], ['status'], ['author_id'], ['trid']],
            ],
            [
                'name' => "{$prefix}terms",
                'columns' => [
                    ['type' => 'primary', 'name' => 'id'],
                    ['type' => 'string(32)', 'name' => 'taxonomy', 'nullable' => false],
                    ['type' => 'string(255)', 'name' => 'name', 'nullable' => false],
                    ['type' => 'string(255)', 'name' => 'slug', 'nullable' => false],
                    ['type' => 'integer', 'name' => 'count', 'nullable' => false, 'default' => 0],
                ],
                'indexes' => [['taxonomy'], ['slug']],
            ],
            [
                'name' => "{$prefix}term_relationships",
                'columns' => [
                    ['type' => 'integer', 'name' => 'object_id', 'nullable' => false],
                    ['type' => 'integer', 'name' => 'term_id', 'nullable' => false],
                ],
                'indexes' => [['object_id'], ['term_id']],
                'uniques' => [['object_id', 'term_id']],
            ],
        ];

        return $this->createTables($db, $tables, $force);
    }

    /** @return array{created: int, skipped: int} */
    private function initTranslationTables(Database $db, string $prefix, bool $force): array
    {
        $tables = [
            [
                'name' => "{$prefix}icl_translations",
                'columns' => [
                    ['type' => 'primary', 'name' => 'translation_id'],
                    ['type' => 'string(60)', 'name' => 'element_type', 'nullable' => false],
                    ['type' => 'integer', 'name' => 'element_id', 'nullable' => false],
                    ['type' => 'bigInteger', 'name' => 'trid', 'nullable' => false],
                    ['type' => 'string(7)', 'name' => 'language_code', 'nullable' => false],
                    ['type' => 'string(7)', 'name' => 'source_language_code', 'nullable' => true],
                ],
                'indexes' => [['trid'], ['language_code']],
                'uniques' => [['element_type', 'element_id', 'language_code']],
            ],
            [
                'name' => "{$prefix}post_translations",
                'columns' => [
                    ['type' => 'primary', 'name' => 'id'],
                    ['type' => 'integer', 'name' => 'post_id', 'nullable' => false],
                    ['type' => 'string(5)', 'name' => 'locale', 'nullable' => false],
                    ['type' => 'string(255)', 'name' => 'title', 'nullable' => true],
                    ['type' => 'string(255)', 'name' => 'slug', 'nullable' => true],
                    ['type' => 'text', 'name' => 'content', 'nullable' => true],
                    ['type' => 'text', 'name' => 'excerpt', 'nullable' => true],
                ],
                'indexes' => [['post_id']],
                'uniques' => [['post_id', 'locale']],
            ],
        ];

        return $this->createTables($db, $tables, $force);
    }

    /** @return array{created: int, skipped: int} */
    private function initGlobalTables(Database $db, bool $force): array
    {
        $tables = [
            [
                'name' => 'schema_registry',
                'columns' => [
                    ['type' => 'primary', 'name' => 'id'],
                    ['type' => 'string(100)', 'name' => 'module', 'nullable' => false],
                    ['type' => 'string(20)', 'name' => 'schema_version', 'nullable' => false, 'default' => '1.0.0'],
                    ['type' => 'string(64)', 'name' => 'schema_hash', 'nullable' => false],
                    ['type' => 'datetime', 'name' => 'synced_at', 'nullable' => false],
                ],
                'uniques' => [['module']],
            ],
            [
                'name' => 'pw_migrations',
                'columns' => [
                    ['type' => 'primary', 'name' => 'id'],
                    ['type' => 'string(255)', 'name' => 'migration', 'nullable' => false],
                    ['type' => 'integer', 'name' => 'batch', 'nullable' => false],
                    ['type' => 'datetime', 'name' => 'executed_at', 'nullable' => false, 'default' => 'CURRENT_TIMESTAMP'],
                ],
                'uniques' => [['migration']],
            ],
            [
                'name' => 'plugin_registry',
                'columns' => [
                    ['type' => 'primary', 'name' => 'id'],
                    ['type' => 'string(191)', 'name' => 'name', 'nullable' => false],
                    ['type' => 'string(20)', 'name' => 'version', 'nullable' => false],
                    ['type' => 'longText', 'name' => 'metadata', 'nullable' => true],
                    ['type' => 'boolean', 'name' => 'enabled', 'nullable' => false, 'default' => true],
                    ['type' => 'string(64)', 'name' => 'schema_hash', 'nullable' => false, 'default' => ''],
                    ['type' => 'datetime', 'name' => 'installed_at', 'nullable' => false],
                    ['type' => 'datetime', 'name' => 'updated_at', 'nullable' => false],
                ],
                'uniques' => [['name']],
            ],
            [
                'name' => 'auth_tokens',
                'columns' => [
                    ['type' => 'string(64)', 'name' => 'id', 'nullable' => false, 'primary' => true],
                    ['type' => 'text', 'name' => 'payload', 'nullable' => false],
                    ['type' => 'datetime', 'name' => 'expires_at', 'nullable' => true],
                    ['type' => 'datetime', 'name' => 'created_at', 'nullable' => false],
                ],
            ],
            [
                'name' => 'pw_options',
                'columns' => [
                    ['type' => 'primary', 'name' => 'id'],
                    ['type' => 'string(191)', 'name' => 'option_name', 'nullable' => false],
                    ['type' => 'longText', 'name' => 'option_value', 'nullable' => true],
                    ['type' => 'string(20)', 'name' => 'autoload', 'nullable' => false, 'default' => 'yes'],
                ],
                'indexes' => [['autoload']],
                'uniques' => [['option_name']],
            ],
        ];

        return $this->createTables($db, $tables, $force);
    }

    /**
     * @param array<int, array{name: string, columns: array<int, array{type: string, name: string, nullable?: bool, default?: mixed, primary?: bool}>, indexes?: array<int, array<int, string>>, uniques?: array<int, array<int, string>>}> $definitions
     * @return array{created: int, skipped: int}
     */
    private function createTables(Database $db, array $definitions, bool $force): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($definitions as $def) {
            $name = $def['name'];
            if ($name === '') {
                continue;
            }

            if ($db->hasTable($name)) {
                if ($force) {
                    $schema = $db->table($name)->getSchema();
                    $schema->declareDropped();
                    $schema->save();
                    $this->line("  [DROP] {$name}");
                } else {
                    $skipped++;
                    continue;
                }
            }

            $schema = $db->table($name)->getSchema();

            foreach ($def['columns'] as $col) {
                $isPrimary = $col['primary'] ?? ($col['type'] === 'primary');
                $colName = $col['name'];

                if ($isPrimary) {
                    $schema->primary($colName);
                    continue;
                }

                $type = $col['type'];
                if ($colName === '') {
                    continue;
                }
                $column = $schema->column($colName);

                if (str_starts_with($type, 'string(')) {
                    $len = (int) substr($type, 7, -1);
                    $column->string($len);
                } elseif ($type === 'text') {
                    $column->type('text');
                } elseif ($type === 'longText') {
                    $column->type('longText');
                } elseif ($type === 'integer') {
                    $column->type('integer');
                } elseif ($type === 'bigInteger') {
                    $column->type('bigInteger');
                } elseif ($type === 'datetime') {
                    $column->datetime();
                } elseif ($type === 'boolean') {
                    $column->type('boolean');
                } elseif ($type === 'json') {
                    $column->type('json');
                } else {
                    $column->type('string');
                }

                if (!($col['nullable'] ?? true)) {
                    $column->nullable(false);
                } else {
                    $column->nullable(true);
                }

                if (isset($col['default'])) {
                    $column->defaultValue($col['default']);
                }
            }

            foreach ($def['indexes'] ?? [] as $idxCols) {
                $schema->index($idxCols);
            }

            foreach ($def['uniques'] ?? [] as $uqCols) {
                $schema->index($uqCols)->unique();
            }

            $schema->save();
            $created++;
            $this->line("  [OK] {$name}");
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
