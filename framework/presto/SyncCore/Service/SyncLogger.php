<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Service;

use Cycle\Database\DatabaseInterface;
use Witals\Framework\Application;

/**
 * Writes human-readable sync activity to bow_sync_logs: system events (job
 * started/finished), batch events (page fetched) and per-item events. The POS
 * "Nhật ký" screen reads this table; a clear button truncates it.
 */
final class SyncLogger
{
    public function __construct(
        private Application $app,
        private DatabaseInterface $db,
    ) {
    }

    public function system(string $message, array $context = [], string $platform = '', string $resource = ''): void
    {
        $this->write('info', 'system', $platform, $resource, '', null, $message, $context);
    }

    public function batch(string $message, array $context = [], string $platform = '', string $resource = ''): void
    {
        $this->write('info', 'batch', $platform, $resource, '', null, $message, $context);
    }

    public function item(string $message, array $context = [], string $platform = '', string $resource = '', string $status = 'success'): void
    {
        $level = $status === 'failed' || $status === 'error' ? 'error' : 'info';
        $this->write($level, 'item', $platform, $resource, '', null, $message, $context);
    }

    public function error(string $message, array $context = [], string $platform = '', string $resource = ''): void
    {
        $this->write('error', 'system', $platform, $resource, '', null, $message, $context);
    }

    public function warn(string $message, array $context = [], string $platform = '', string $resource = ''): void
    {
        $this->write('warn', 'system', $platform, $resource, '', null, $message, $context);
    }

    private function write(
        string $level,
        string $category,
        string $platform,
        string $resource,
        string $direction,
        ?int $jobId,
        string $message,
        array $context = [],
    ): void {
        $prefix = (string) ($this->app->config('ecommerce.table_prefix', 'pw_') ?: 'pw_');
        $table = $prefix . 'sync_logs';

        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->insert($table)->values([
            'level' => $level,
            'category' => $category,
            'platform' => $platform,
            'resource' => $resource,
            'direction' => $direction,
            'job_id' => $jobId,
            'message' => $message,
            'context' => $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => time(),
        ])->run();
    }
}