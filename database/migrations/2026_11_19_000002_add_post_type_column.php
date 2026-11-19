<?php

declare(strict_types=1);

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Table;
use PrestoWorld\Database\MigrationInterface;

/**
 * 2026_11_19_000002_add_post_type_column
 *
 * Thêm cột post_type (WordPress-compatible alias) vào bảng posts để
 * các template/vendor code (wp-bridge) có thể filter/query theo cột này.
 * Cột type trong schema PrestoWorld tương đương với post_type trong WP.
 */
return new class implements MigrationInterface {
    public function up(DatabaseInterface $db, string $prefix): void
    {
        $posts = $prefix . 'posts';
        if (!$db->hasTable($posts)) {
            return;
        }

        $table = $db->table($posts);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();

        if (!$schema->hasColumn('post_type')) {
            $schema->column('post_type')->string(20)->nullable();
            $schema->save();
        }
    }

    public function down(DatabaseInterface $db, string $prefix): void
    {
        $posts = $prefix . 'posts';
        if (!$db->hasTable($posts)) {
            return;
        }

        $table = $db->table($posts);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();
        if ($schema->hasColumn('post_type')) {
            $schema->dropColumn('post_type');
            $schema->save();
        }
    }
};
