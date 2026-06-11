<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Schema;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Schema\AbstractTable;

/**
 * Performance-First Post Type & Taxonomy Schema Manager
 */
class PostTypeSchemaManager
{
    protected DatabaseInterface $db;
    protected string $tablePrefix = 'pw_';
    protected string $stateFilePath;
    protected array $syncedStates = [];

    public function __construct(DatabaseInterface $db, string $storagePath = '')
    {
        $this->db = $db;
        $this->stateFilePath = ($storagePath ?: sys_get_temp_dir()) . '/pw_schema_state.json';
        $this->loadSyncedStates();
    }

    public function __destruct()
    {
        if (!empty($this->syncedStates)) {
            @file_put_contents($this->stateFilePath, json_encode($this->syncedStates));
        }
    }

    /**
     * Register a post type and synchronize its database schema
     */
    public function register(string $postType, array $args = []): void
    {
        $stateHash = md5(serialize($args));
        $stateKey = "pt_{$postType}";

        $this->ensureMasterTable();
        $this->ensureTaxonomyTables();

        if (($this->syncedStates[$stateKey] ?? null) === $stateHash && !defined('PW_FORCE_MIGRATE')) {
            return;
        }

        $tableName = $this->tablePrefix . "post_" . $postType;
        $schema = $this->db->table($tableName)->getSchema();
        
        $schema->column('post_id')->integer()->nullable(false);
        $schema->index(['post_id'])->unique();

        if (isset($args['columns']) && is_array($args['columns'])) {
            $this->syncCustomColumns($schema, $args['columns']);
        }

        $schema->save();
        $this->syncedStates[$stateKey] = $stateHash;
    }

    /**
     * Register a taxonomy and synchronize its database schema
     */
    public function registerTaxonomy(string $taxonomy, array $args = []): void
    {
        $stateHash = md5(serialize($args));
        $stateKey = "tax_{$taxonomy}";

        $this->ensureTaxonomyTables();

        if (($this->syncedStates[$stateKey] ?? null) === $stateHash && !defined('PW_FORCE_MIGRATE')) {
            return;
        }

        $tableName = $this->tablePrefix . "tax_" . $taxonomy;
        $schema = $this->db->table($tableName)->getSchema();
        
        $schema->column('term_id')->integer()->nullable(false);
        $schema->index(['term_id'])->unique();
        
        // Add hierarchy support
        if ($args['hierarchical'] ?? false) {
            $schema->column('parent_id')->integer()->nullable()->index();
        }

        if (isset($args['columns']) && is_array($args['columns'])) {
            $this->syncCustomColumns($schema, $args['columns']);
        }

        $schema->save();
        $this->syncedStates[$stateKey] = $stateHash;
    }

    /**
     * Register Meta Box as Column
     */
    public function registerMeta(string $postType, string $metaKey, string $type = 'string', array $options = []): void
    {
        $stateHash = md5($type . serialize($options));
        $stateKey = "meta_{$postType}_{$metaKey}";

        if (($this->syncedStates[$stateKey] ?? null) === $stateHash && !defined('PW_FORCE_MIGRATE')) {
            return;
        }

        $tableName = $this->tablePrefix . "post_" . $postType;
        if (!$this->db->hasTable($tableName)) {
            $this->register($postType);
        }

        $schema = $this->db->table($tableName)->getSchema();
        $column = $schema->column($metaKey);
        
        $this->mapColumnType($column, $type, $options);
        
        if ($options['nullable'] ?? true) { $column->nullable(); }
        if (isset($options['default'])) { $column->defaultValue($options['default']); }
        if ($options['index'] ?? false) { $schema->index([$metaKey]); }

        $schema->save();
        $this->syncedStates[$stateKey] = $stateHash;
    }

    protected function ensureMasterTable(): void
    {
        $stateKey = 'master_table';
        if (isset($this->syncedStates[$stateKey]) && !defined('PW_FORCE_MIGRATE')) {
            return;
        }

        $schema = $this->db->table($this->tablePrefix . 'posts')->getSchema();
        $schema->primary('id');
        $schema->column('post_type')->string(32)->index();
        $schema->column('title')->string(255);
        $schema->column('slug')->string(255)->index();
        $schema->column('status')->string(20)->defaultValue('publish')->index();
        $schema->column('author_id')->integer()->nullable()->index();
        $schema->column('created_at')->datetime()->defaultValue('CURRENT_TIMESTAMP');
        $schema->column('updated_at')->datetime()->nullable();
        $schema->column('compact_meta')->json()->nullable();

        $schema->save();
        $this->syncedStates[$stateKey] = 'active';
    }

    protected function ensureTaxonomyTables(): void
    {
        $stateKey = 'tax_tables';
        if (isset($this->syncedStates[$stateKey]) && !defined('PW_FORCE_MIGRATE')) {
            return;
        }

        $terms = $this->db->table($this->tablePrefix . 'terms')->getSchema();
        $terms->primary('id');
        $terms->column('taxonomy')->string(32)->index();
        $terms->column('name')->string(255);
        $terms->column('slug')->string(255)->index();
        $terms->column('count')->integer()->defaultValue(0);
        $terms->save();

        $rel = $this->db->table($this->tablePrefix . 'term_relationships')->getSchema();
        $rel->column('object_id')->integer()->index();
        $rel->column('term_id')->integer()->index();
        $rel->index(['object_id', 'term_id'])->unique();
        $rel->save();

        $this->syncedStates[$stateKey] = 'active';
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
            case 'boolean': $column->boolean(); break;
            case 'json':    $column->json(); break;
            case 'text':    $column->text(); break;
            default:        $column->string(255); break;
        }
    }

    protected function syncCustomColumns(AbstractTable $schema, array $columns): void
    {
        foreach ($columns as $name => $definition) {
            $type = is_string($definition) ? $definition : ($definition['type'] ?? 'string');
            $column = $schema->column($name);
            
            if ($type === 'decimal') {
                $column->decimal(15, 2);
                continue;
            }

            if (method_exists($column, $type)) {
                $column->$type();
            }
        }
    }
}
