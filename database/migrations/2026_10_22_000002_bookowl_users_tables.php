<?php

declare(strict_types=1);

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Table;
use PrestoWorld\Database\MigrationInterface;

/**
 * 2026_10_22_000002_bookowl_users_tables
 *
 * Tạo bảng cho domain Bookowl tài khoản & quyền.
 * Prefix lấy từ PW_TABLE_PREFIX (mặc định pw_), do SchemaMigrationManager truyền vào.
 *
 * - users: tài khoản native Bookowl
 * - role_caps: vai trò & capability
 */
return new class implements MigrationInterface {
    public function up(DatabaseInterface $db, string $prefix): void
    {
        $this->users($db, $prefix);
        $this->roleCaps($db, $prefix);
    }

    public function down(DatabaseInterface $db, string $prefix): void
    {
        foreach ([$prefix . 'role_caps', $prefix . 'users'] as $name) {
            if (!$db->hasTable($name)) {
                continue;
            }
            $table = $db->table($name);
            if ($table instanceof Table) {
                $schema = $table->getSchema();
                $schema->declareDropped();
                $schema->save();
            }
        }
    }

    private function users(DatabaseInterface $db, string $prefix): void
    {
        $name = $prefix . 'users';

        if ($db->hasTable($name)) {
            $this->mergeUsers($db, $name);

            return;
        }

        $table = $db->table($name);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->column('username')->string(60)->nullable(false);
        $schema->column('display_name')->string(100)->nullable();
        $schema->column('email')->string(191)->nullable(false);
        $schema->column('phone')->string(20)->nullable();
        $schema->column('avatar_attachment')->integer()->nullable();
        $schema->column('role')->string(30)->nullable(false)->defaultValue('reader');
        $schema->column('status')->string(20)->nullable(false)->defaultValue('active');
        $schema->column('password_hash')->string(255)->nullable(false);
        $schema->column('created_at')->datetime()->nullable(false)->defaultValue(new \DateTimeImmutable('now'));
        $schema->column('updated_at')->datetime()->nullable(false)->defaultValue(new \DateTimeImmutable('now'));
        $schema->index(['username'])->unique();
        $schema->index(['email'])->unique();
        $schema->index(['phone']);
        $schema->index(['role']);
        $schema->index(['status']);
        $schema->save();
    }

    /**
     * The core migration (2026_10_09_000001) owns the shared `users` table and
     * creates it with the WordPress compatible columns. The Bookowl account
     * model only adds columns to it - it never replaces the table.
     */
    private function mergeUsers(DatabaseInterface $db, string $name): void
    {
        $table = $db->table($name);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();

        if (!$schema->hasColumn('phone')) {
            $schema->column('phone')->string(20)->nullable();
        }

        if (!$schema->hasColumn('avatar_attachment')) {
            $schema->column('avatar_attachment')->integer()->nullable();
        }

        if (!$schema->hasColumn('status')) {
            $schema->column('status')->string(20)->nullable(false)->defaultValue('active');
        }

        $schema->save();
    }

    private function roleCaps(DatabaseInterface $db, string $prefix): void
    {
        $name = $prefix . 'role_caps';
        if ($db->hasTable($name)) {
            return;
        }

        $table = $db->table($name);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->column('role')->string(30)->nullable(false);
        $schema->column('capability')->string(60)->nullable(false);
        $schema->column('created_at')->datetime()->nullable(false)->defaultValue(new \DateTimeImmutable('now'));
        $schema->index(['role', 'capability'])->unique();
        $schema->save();
    }
};
