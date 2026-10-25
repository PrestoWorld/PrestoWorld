<?php

declare(strict_types=1);

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Table;
use PrestoWorld\Database\MigrationInterface;

/**
 * 001_sync_tables
 *
 * Generic synchronization storage shared by every platform connector
 * (Nhanh.vn, KiotViet, ...). Connector modules only own their API adapters and
 * resource catalogs, never their own job/record/mapping tables.
 */
return new class implements MigrationInterface {
    public function up(DatabaseInterface $db, string $prefix): void
    {
        $this->settings($db, $prefix);
        $this->jobs($db, $prefix);
        $this->records($db, $prefix);
        $this->mappings($db, $prefix);
    }

    public function down(DatabaseInterface $db, string $prefix): void
    {
        foreach (['sync_mappings', 'sync_records', 'sync_jobs', 'sync_settings'] as $name) {
            if ($db->hasTable($prefix . $name)) {
                $db->table($prefix . $name)->getSchema()->drop();
            }
        }
    }

    private function settings(DatabaseInterface $db, string $prefix): void
    {
        $table = $this->table($db, $prefix, 'sync_settings');
        $table->primary('id');
        $table->column('platform')->string(48);
        $table->column('inbound_enabled')->integer()->defaultValue(1);
        $table->column('outbound_enabled')->integer()->defaultValue(0);
        $table->column('settings')->text()->nullable();
        $table->column('created_at')->bigInteger()->defaultValue(0);
        $table->column('updated_at')->bigInteger()->defaultValue(0);
        $table->index(['platform'])->unique();
        $table->save();
    }

    private function jobs(DatabaseInterface $db, string $prefix): void
    {
        $table = $this->table($db, $prefix, 'sync_jobs');
        $table->primary('id');
        $table->column('platform')->string(48);
        $table->column('direction')->string(16);
        $table->column('resource')->string(96);
        $table->column('trigger_type')->string(32)->defaultValue('manual');
        $table->column('status')->string(24)->defaultValue('queued');
        $table->column('total')->bigInteger()->defaultValue(0);
        $table->column('processed')->bigInteger()->defaultValue(0);
        $table->column('succeeded')->bigInteger()->defaultValue(0);
        $table->column('failed')->bigInteger()->defaultValue(0);
        $table->column('skipped')->bigInteger()->defaultValue(0);
        $table->column('page')->integer()->defaultValue(0);
        $table->column('message')->text()->nullable();
        $table->column('options')->text()->nullable();
        $table->column('started_at')->bigInteger()->defaultValue(0);
        $table->column('finished_at')->bigInteger()->defaultValue(0);
        $table->column('created_at')->bigInteger()->defaultValue(0);
        $table->column('updated_at')->bigInteger()->defaultValue(0);
        $table->index(['platform']);
        $table->index(['status']);
        $table->index(['created_at']);
        $table->save();
    }

    private function records(DatabaseInterface $db, string $prefix): void
    {
        $table = $this->table($db, $prefix, 'sync_records');
        $table->primary('id');
        $table->column('job_id')->bigInteger();
        $table->column('platform')->string(48);
        $table->column('resource')->string(96);
        $table->column('direction')->string(16);
        $table->column('external_id')->string(96)->defaultValue('');
        $table->column('local_id')->string(96)->defaultValue('');
        $table->column('status')->string(24)->defaultValue('success');
        $table->column('action')->string(24)->defaultValue('');
        $table->column('message')->text()->nullable();
        $table->column('payload')->text()->nullable();
        $table->column('created_at')->bigInteger()->defaultValue(0);
        $table->index(['job_id']);
        $table->index(['external_id']);
        $table->index(['created_at']);
        $table->save();
    }

    private function mappings(DatabaseInterface $db, string $prefix): void
    {
        $table = $this->table($db, $prefix, 'sync_mappings');
        $table->primary('id');
        $table->column('platform')->string(48);
        $table->column('resource')->string(96);
        $table->column('business_id')->string(64)->defaultValue('');
        $table->column('external_id')->string(96);
        $table->column('local_id')->string(96);
        $table->column('meta')->text()->nullable();
        $table->column('created_at')->bigInteger()->defaultValue(0);
        $table->column('updated_at')->bigInteger()->defaultValue(0);
        $table->index(['platform', 'resource', 'external_id'])->unique();
        $table->index(['local_id']);
        $table->save();
    }

    private function table(DatabaseInterface $db, string $prefix, string $name): \Cycle\Database\Schema\AbstractTable
    {
        $table = $db->table($prefix . $name);
        if (!$table instanceof Table) {
            throw new \RuntimeException(sprintf(
                'Expected concrete Cycle table for %s, got %s',
                $prefix . $name,
                $table::class,
            ));
        }

        return $table->getSchema();
    }
};
