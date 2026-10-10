<?php

declare(strict_types=1);

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Table;
use PrestoWorld\Database\MigrationInterface;

/**
 * 2026_10_09_000001_add_users_and_post_meta
 *
 * Đồng bộ schema với spec 05 §5.2.1:
 * - Thêm bảng pw_users (email unique, password_hash, display_name, role...).
 * - pw_posts: compact_meta (json) → meta (jsonb) + thêm content, published_at,
 *   unique slug, GIN index trên meta.
 */
return new class implements MigrationInterface {
    public function up(DatabaseInterface $db, string $prefix): void
    {
        $posts = $prefix . 'posts';
        $users = $prefix . 'users';

        if (!$db->hasTable($users)) {
            $table = $db->table($users);
            if (!$table instanceof Table) {
                return;
            }
            $schema = $table->getSchema();
            $schema->primary('id');
            $schema->column('email')->string(255)->nullable(false);
            $schema->column('username')->string(100)->nullable();
            $schema->column('password_hash')->string(255)->nullable(false);
            $schema->column('display_name')->string(100)->nullable();
            $schema->column('role')->string(50)->nullable(false)->defaultValue('subscriber');
            $schema->column('created_at')->datetime()->nullable(false)->defaultValue('CURRENT_TIMESTAMP');
            $schema->column('updated_at')->datetime()->nullable(false)->defaultValue('CURRENT_TIMESTAMP');
            $schema->index(['email'])->unique();
            $schema->index(['role']);
            $schema->save();
        }

        if ($db->hasTable($posts)) {
            $table = $db->table($posts);
            if (!$table instanceof Table) {
                return;
            }
            $schema = $table->getSchema();

            if (!$schema->hasColumn('meta')) {
                $schema->column('meta')->type('jsonb')->nullable()->defaultValue('{}');
            }

            if (!$schema->hasColumn('content')) {
                $schema->column('content')->type('text')->nullable();
            }

            if (!$schema->hasColumn('published_at')) {
                $schema->column('published_at')->datetime()->nullable();
            }

            if (!$schema->hasColumn('compact_meta')) {
                $schema->index(['slug'])->unique();
            }

            $schema->save();

            if ($schema->hasColumn('compact_meta')) {
                $schema->dropColumn('compact_meta');
                $schema->save();
            }

            // GIN index (Postgres) — bỏ qua trên driver khác.
            try {
                $db->execute("CREATE INDEX IF NOT EXISTS idx_posts_meta ON {$posts} USING GIN (meta)");
            } catch (\Throwable) {
                // SQLite/MySQL: bỏ qua.
            }
        }
    }

    public function down(DatabaseInterface $db, string $prefix): void
    {
        $posts = $prefix . 'posts';

        if ($db->hasTable($posts)) {
            $table = $db->table($posts);
            if ($table instanceof Table) {
                $schema = $table->getSchema();

                if (!$schema->hasColumn('compact_meta')) {
                    $schema->column('compact_meta')->type('json')->nullable();
                    $schema->save();
                }

                if ($schema->hasColumn('meta')) {
                    $schema->dropColumn('meta');
                    $schema->save();
                }
            }
        }

        if ($db->hasTable($prefix . 'users')) {
            $table = $db->table($prefix . 'users');
            if ($table instanceof Table) {
                $schema = $table->getSchema();
                $schema->declareDropped();
                $schema->save();
            }
        }
    }
};
