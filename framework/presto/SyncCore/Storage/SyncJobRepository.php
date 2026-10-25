<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Storage;

use Cycle\Database\DatabaseInterface;
use PrestoWorld\Core\SyncCore\Domain\Dto\SyncJob;
use PrestoWorld\Core\SyncCore\Domain\Enums\SyncDirection;
use PrestoWorld\Core\SyncCore\Domain\Enums\SyncStatus;
use PrestoWorld\Core\SyncCore\Domain\Enums\SyncTrigger;

final class SyncJobRepository
{
    public function __construct(
        private DatabaseInterface $db,
        private string $prefix = 'pw_',
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(
        string $platform,
        SyncDirection $direction,
        string $resource,
        SyncTrigger $trigger,
        array $options = [],
        int $total = 0,
    ): int {
        $now = time();

        return (int) $this->db->insert($this->table('sync_jobs'))->values([
            'platform' => $platform,
            'direction' => $direction->value,
            'resource' => $resource,
            'trigger_type' => $trigger->value,
            'status' => SyncStatus::QUEUED->value,
            'total' => max(0, $total),
            'options' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        ])->run();
    }

    public function find(int $id): ?SyncJob
    {
        $row = $this->db->select('*')->from($this->table('sync_jobs'))
            ->where('id', $id)
            ->run()->fetch();

        return $row === false ? null : SyncJob::fromRow((array) $row);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{items: list<SyncJob>, total: int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $query = $this->db->select('*')->from($this->table('sync_jobs'));
        $count = $this->db->select('*')->from($this->table('sync_jobs'));

        foreach (['platform', 'direction', 'resource', 'status'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
                $count->where($column, $value);
            }
        }

        $rows = $query->orderBy('id', 'DESC')
            ->offset(max(0, ($page - 1) * $perPage))
            ->limit($perPage)
            ->run()->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $items[] = SyncJob::fromRow((array) $row);
        }

        return ['items' => $items, 'total' => (int) $count->count()];
    }

    public function markRunning(int $id): void
    {
        $now = time();
        $this->db->update($this->table('sync_jobs'), [
            'status' => SyncStatus::RUNNING->value,
            'started_at' => $now,
            'updated_at' => $now,
        ], ['id' => $id])->run();
    }

    public function progress(int $id, int $total, int $processed, int $succeeded, int $failed, int $skipped, int $page): void
    {
        $this->db->update($this->table('sync_jobs'), [
            'total' => max(0, $total),
            'processed' => max(0, $processed),
            'succeeded' => max(0, $succeeded),
            'failed' => max(0, $failed),
            'skipped' => max(0, $skipped),
            'page' => max(0, $page),
            'updated_at' => time(),
        ], ['id' => $id])->run();
    }

    public function finish(int $id, SyncStatus $status, ?string $message = null): void
    {
        $now = time();
        $this->db->update($this->table('sync_jobs'), [
            'status' => $status->value,
            'message' => $message,
            'finished_at' => $now,
            'updated_at' => $now,
        ], ['id' => $id])->run();
    }

    public function cancel(int $id): bool
    {
        $job = $this->find($id);
        if ($job === null || SyncStatus::from($job->status)->isFinished()) {
            return false;
        }

        $this->finish($id, SyncStatus::CANCELLED, 'Đã huỷ bởi người dùng');

        return true;
    }

    public function isCancelled(int $id): bool
    {
        $row = $this->db->select('status')->from($this->table('sync_jobs'))
            ->where('id', $id)->run()->fetch();

        return $row !== false && ((array) $row)['status'] === SyncStatus::CANCELLED->value;
    }

    public function hasRunning(string $platform, string $resource): bool
    {
        $count = $this->db->select('*')->from($this->table('sync_jobs'))
            ->where('platform', $platform)
            ->where('resource', $resource)
            ->where('status', SyncStatus::RUNNING->value)
            ->count();

        return (int) $count > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(string $platform): array
    {
        $last = $this->db->select('*')->from($this->table('sync_jobs'))
            ->where('platform', $platform)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->run()->fetch();

        return [
            'total' => (int) $this->db->select('*')->from($this->table('sync_jobs'))->where('platform', $platform)->count(),
            'running' => (int) $this->db->select('*')->from($this->table('sync_jobs'))->where('platform', $platform)->where('status', SyncStatus::RUNNING->value)->count(),
            'failed' => (int) $this->db->select('*')->from($this->table('sync_jobs'))->where('platform', $platform)->where('status', SyncStatus::FAILED->value)->count(),
            'last' => $last === false ? null : SyncJob::fromRow((array) $last)->toArray(),
        ];
    }

    private function table(string $name): string
    {
        return $this->prefix . $name;
    }
}
