<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Storage;

use Cycle\Database\DatabaseInterface;
use PrestoWorld\Core\SyncCore\Domain\Enums\RecordStatus;

final class SyncRecordRepository
{
    public function __construct(
        private DatabaseInterface $db,
        private string $prefix = 'pw_',
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function add(
        int $jobId,
        string $platform,
        string $resource,
        string $direction,
        string $externalId,
        string $localId,
        RecordStatus $status,
        string $action = '',
        ?string $message = null,
        array $payload = [],
    ): void {
        $this->db->insert($this->table('sync_records'))->values([
            'job_id' => $jobId,
            'platform' => $platform,
            'resource' => $resource,
            'direction' => $direction,
            'external_id' => substr($externalId, 0, 96),
            'local_id' => substr($localId, 0, 96),
            'status' => $status->value,
            'action' => $action,
            'message' => $message,
            'payload' => $payload === []
                ? null
                : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => time(),
        ])->run();
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function paginate(int $jobId, array $filters, int $page, int $perPage): array
    {
        $query = $this->db->select('*')->from($this->table('sync_records'))->where('job_id', $jobId);
        $count = $this->db->select('*')->from($this->table('sync_records'))->where('job_id', $jobId);

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $query->where('status', $status);
            $count->where('status', $status);
        }

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $query->where(fn ($q) => $q
                ->where('external_id', 'LIKE', '%' . $keyword . '%')
                ->orWhere('local_id', 'LIKE', '%' . $keyword . '%')
                ->orWhere('message', 'LIKE', '%' . $keyword . '%'));
            $count->where(fn ($q) => $q
                ->where('external_id', 'LIKE', '%' . $keyword . '%')
                ->orWhere('local_id', 'LIKE', '%' . $keyword . '%')
                ->orWhere('message', 'LIKE', '%' . $keyword . '%'));
        }

        $rows = $query->orderBy('id', 'DESC')
            ->offset(max(0, ($page - 1) * $perPage))
            ->limit($perPage)
            ->run()->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $row['payload'] = is_string($row['payload'] ?? null) ? json_decode($row['payload'], true) : null;
            $items[] = $row;
        }

        return ['items' => $items, 'total' => (int) $count->count()];
    }

    /**
     * @return array<string, int>
     */
    public function summary(int $jobId): array
    {
        $summary = ['success' => 0, 'failed' => 0, 'skipped' => 0];
        $rows = $this->db->select('status')->from($this->table('sync_records'))
            ->where('job_id', $jobId)
            ->run()->fetchAll();

        foreach ($rows as $row) {
            $status = (string) ((array) $row)['status'];
            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }
        }

        return $summary;
    }

    public function countByPlatform(string $platform): int
    {
        return (int) $this->db->select('*')->from($this->table('sync_records'))
            ->where('platform', $platform)
            ->count();
    }

    private function table(string $name): string
    {
        return $this->prefix . $name;
    }
}
