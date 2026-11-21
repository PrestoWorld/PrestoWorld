<?php

declare(strict_types=1);

use Cycle\Database\DatabaseInterface;
use PrestoWorld\Database\MigrationInterface;

/**
 * 004_sync_logs
 *
 * Unified, human-readable activity log for sync runs: system events (job
 * started/finished), batch events (page fetched) and per-item events (each
 * upsert/skip/failure). Written by the SyncLogger and shown on the POS
 * "Nhật ký" screen. Can be truncated from the UI to reclaim space.
 */
return new class implements MigrationInterface {
    public function up(DatabaseInterface $db, string $prefix): void
    {
        $table = $db->table($prefix . 'sync_logs')->getSchema();
        $table->primary('id');
        $table->column('level')->string(16)->defaultValue('info');       // info | warn | error
        $table->column('category')->string(16)->defaultValue('system');  // system | batch | item
        $table->column('platform')->string(48)->defaultValue('');
        $table->column('resource')->string(96)->defaultValue('');
        $table->column('direction')->string(16)->defaultValue('');
        $table->column('job_id')->bigInteger()->nullable();
        $table->column('message')->text();
        $table->column('context')->text()->nullable();
        $table->column('created_at')->bigInteger()->defaultValue(0);
        $table->index(['created_at']);
        $table->index(['platform', 'resource']);
        $table->index(['level']);
        $table->index(['category']);
        $table->save();
    }

    public function down(DatabaseInterface $db, string $prefix): void
    {
        if ($db->hasTable($prefix . 'sync_logs')) {
            $db->table($prefix . 'sync_logs')->drop();
        }
    }
};