<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Schema;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Schema\AbstractTable;

/**
 * Performance-First Post Type Schema Manager
 * 
 * Uses a State Hash mechanism to avoid redundant database schema inspections.
 * Synchronizes only when code definitions change, ensuring near-zero impact 
 * on runtime performance for end users.
 */
class PostTypeSchemaManager
{
    protected DatabaseInterface $db;
    protected string $tablePrefix = 'pw_';
    protected array $registry = [];
    protected string $stateFilePath;
    protected array $syncedStates = [];

    public function __construct(DatabaseInterface $db, string $storagePath = '')
    {
        $this->db = $db;
        $this->stateFilePath = ($storagePath ?: sys_get_temp_dir()) . '/pw_schema_state.json';
        $this->loadSyncedStates();
    }

    /**
     * Terminate - Save state at the end of lifecycle if changed
     */
    public function __destruct()
    {
        if (!empty($this->syncedStates)) {
            file_put_contents($this->stateFilePath, json_encode($this->syncedStates));
        }
    }

    /**
     * Register and Synchronize only if definition has changed
     */
    public function register(string $postType, array $args = []): void
    {
        $stateHash = md5(serialize($args));
        $stateKey = "pt_{$postType}";

        // PERFORMANCE: Skip if already synchronized for this code definition
        if (($this->syncedStates[$stateKey] ?? null) === $stateHash && !defined('PW_FORCE_MIGRATE')) {
            return;
        }

        $tableName = $this->tablePrefix . $postType;
        $schema = $this->db->table($tableName)->getSchema();

        $this->ensureBaseColumns($schema);
        
        if (isset($args['columns']) && is_array($args['columns'])) {
            $this->syncCustomColumns($schema, $args['columns']);
        }

        $schema->save();
        $this->syncedStates[$stateKey] = $stateHash;
    }

    /**
     * Register Meta with Performance Optimization
     */
    public function registerMeta(string $postType, string $metaKey, string $type = 'string', array $options = []): void
    {
        $stateHash = md5($type . serialize($options));
        $stateKey = "meta_{$postType}_{$metaKey}";

        // PERFORMANCE: Skip redundant ALTER TABLE operations
        if (($this->syncedStates[$stateKey] ?? null) === $stateHash && !defined('PW_FORCE_MIGRATE')) {
            return;
        }

        $tableName = $this->tablePrefix . $postType;
        $schema = $this->db->table($tableName)->getSchema();

        $column = $schema->column($metaKey);
        $this->mapColumnType($column, $type, $options);

        if ($options['nullable'] ?? true) {
            $column->nullable();
        }
        if (isset($options['default'])) {
            $column->defaultValue($options['default']);
        }
        if ($options['index'] ?? false) {
            $schema->index([$metaKey]);
        }

        $schema->save();
        $this->syncedStates[$stateKey] = $stateHash;
    }

    protected function loadSyncedStates(): void
    {
        if (file_exists($this->stateFilePath)) {
            $this->syncedStates = json_decode(file_get_contents($this->stateFilePath), true) ?: [];
        }
    }

    protected function mapColumnType($column, string $type, array $options): void
    {
        switch ($type) {
            case 'int':
            case 'integer': $column->integer(); break;
            case 'boolean':
            case 'bool':    $column->boolean(); break;
            case 'float':   $column->decimal(10, 2); break;
            case 'json':    $column->json(); break;
            case 'text':    $column->text(); break;
            default:        $column->string(255); break;
        }
    }

    protected function ensureBaseColumns(AbstractTable $schema): void
    {
        $schema->primary('id');
        $schema->column('title')->string(255)->nullable(false);
        $schema->column('slug')->string(255)->nullable(false);
        $schema->column('content')->text()->nullable();
        $schema->column('status')->string(20)->defaultValue('publish');
        $schema->column('author_id')->integer()->nullable();
        $schema->column('created_at')->datetime()->defaultValue('CURRENT_TIMESTAMP');
        $schema->index(['slug'])->unique();
    }

    protected function syncCustomColumns(AbstractTable $schema, array $columns): void
    {
        foreach ($columns as $name => $definition) {
            $type = is_string($definition) ? $definition : ($definition['type'] ?? 'string');
            $column = $schema->column($name);
            if (method_exists($column, $type)) {
                $column->$type();
            }
        }
    }
}
