<?php

declare(strict_types=1);

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Table;
use PrestoWorld\Database\MigrationInterface;

/**
 * 2026_10_10_000001_create_base_tables
 *
 * Creates the core PrestoWorld tables that the installer depends on:
 * - pw_posts (blog content)
 * - pw_options (site configuration, including presto_installed flag)
 *
 * The users table is created by 2026_10_09_000001_add_users_and_post_meta.
 */
return new class implements MigrationInterface {
    public function up(DatabaseInterface $db, string $prefix): void
    {
        $posts   = $prefix . 'posts';
        $options = $prefix . 'options';

        // ── pw_posts ────────────────────────────────────────────────
        if (!$db->hasTable($posts)) {
            $table = $db->table($posts);
            if ($table instanceof Table) {
                $schema = $table->getSchema();
                $schema->primary('id');
                $schema->column('title')->string(255)->nullable(false);
                $schema->column('slug')->string(255)->nullable();
                $schema->column('content')->type('text')->nullable();
                $schema->column('excerpt')->type('text')->nullable();
                $schema->column('status')->string(50)->nullable(false)->defaultValue('draft');
                $schema->column('type')->string(50)->nullable(false)->defaultValue('post');
                $schema->column('author_id')->integer()->nullable();
                $schema->column('parent_id')->integer()->nullable();
                $schema->column('menu_order')->integer()->nullable(false)->defaultValue(0);
                $schema->column('meta')->type('jsonb')->nullable()->defaultValue('{}');
                $schema->column('created_at')->datetime()->nullable(false)->defaultValue('CURRENT_TIMESTAMP');
                $schema->column('updated_at')->datetime()->nullable(false)->defaultValue('CURRENT_TIMESTAMP');
                $schema->column('published_at')->datetime()->nullable();
                $schema->index(['slug'])->unique();
                $schema->index(['status']);
                $schema->index(['type']);
                $schema->index(['author_id']);
                $schema->index(['parent_id']);
                $schema->index(['published_at']);
                $schema->save();
            }
        }

        // ── pw_options ──────────────────────────────────────────────
        if (!$db->hasTable($options)) {
            $table = $db->table($options);
            if ($table instanceof Table) {
                $schema = $table->getSchema();
                $schema->primary('id');
                $schema->column('option_name')->string(255)->nullable(false);
                $schema->column('option_value')->type('longtext')->nullable();
                $schema->column('autoload')->string(20)->nullable(false)->defaultValue('yes');
                $schema->index(['option_name'])->unique();
                $schema->save();
            }
        }
    }

    public function down(DatabaseInterface $db, string $prefix): void
    {
        $posts   = $prefix . 'posts';
        $options = $prefix . 'options';

        if ($db->hasTable($posts)) {
            $table = $db->table($posts);
            if ($table instanceof Table) {
                $schema = $table->getSchema();
                $schema->declareDropped();
                $schema->save();
            }
        }

        if ($db->hasTable($options)) {
            $table = $db->table($options);
            if ($table instanceof Table) {
                $schema = $table->getSchema();
                $schema->declareDropped();
                $schema->save();
            }
        }
    }
};