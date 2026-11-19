<?php

declare(strict_types=1);

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Table;
use PrestoWorld\Database\MigrationInterface;

/**
 * 2026_11_19_000001_add_user_registered_column
 *
 * Thêm cột user_registered (WordPress-compatible) vào bảng users để
 * các template/vendor code (wp-bridge, UsersController) có thể order
 * và select theo cột này thay vì created_at.
 */
return new class implements MigrationInterface {
    public function up(DatabaseInterface $db, string $prefix): void
    {
        $users = $prefix . 'users';
        if (!$db->hasTable($users)) {
            return;
        }

        $table = $db->table($users);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();

        if (!$schema->hasColumn('user_registered')) {
            $schema->column('user_registered')->datetime()->nullable();
            $schema->save();
        }
    }

    public function down(DatabaseInterface $db, string $prefix): void
    {
        $users = $prefix . 'users';
        if (!$db->hasTable($users)) {
            return;
        }

        $table = $db->table($users);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();
        if ($schema->hasColumn('user_registered')) {
            $schema->dropColumn('user_registered');
            $schema->save();
        }
    }
};
