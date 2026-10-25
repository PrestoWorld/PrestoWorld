<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Domain\Dto;

final class SyncJob
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public readonly int $id,
        public readonly string $platform,
        public readonly string $direction,
        public readonly string $resource,
        public readonly string $triggerType,
        public readonly string $status,
        public readonly int $total,
        public readonly int $processed,
        public readonly int $succeeded,
        public readonly int $failed,
        public readonly int $skipped,
        public readonly int $page,
        public readonly ?string $message,
        public readonly array $options,
        public readonly int $startedAt,
        public readonly int $finishedAt,
        public readonly int $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $options = is_string($row['options'] ?? null) ? json_decode($row['options'], true) : null;

        return new self(
            id: (int) ($row['id'] ?? 0),
            platform: (string) ($row['platform'] ?? ''),
            direction: (string) ($row['direction'] ?? ''),
            resource: (string) ($row['resource'] ?? ''),
            triggerType: (string) ($row['trigger_type'] ?? 'manual'),
            status: (string) ($row['status'] ?? 'queued'),
            total: (int) ($row['total'] ?? 0),
            processed: (int) ($row['processed'] ?? 0),
            succeeded: (int) ($row['succeeded'] ?? 0),
            failed: (int) ($row['failed'] ?? 0),
            skipped: (int) ($row['skipped'] ?? 0),
            page: (int) ($row['page'] ?? 0),
            message: isset($row['message']) ? (string) $row['message'] : null,
            options: is_array($options) ? $options : [],
            startedAt: (int) ($row['started_at'] ?? 0),
            finishedAt: (int) ($row['finished_at'] ?? 0),
            createdAt: (int) ($row['created_at'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $progress = $this->total > 0 ? min(100, (int) round($this->processed / $this->total * 100)) : 0;

        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'direction' => $this->direction,
            'resource' => $this->resource,
            'trigger_type' => $this->triggerType,
            'status' => $this->status,
            'total' => $this->total,
            'processed' => $this->processed,
            'succeeded' => $this->succeeded,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'page' => $this->page,
            'progress' => $progress,
            'message' => $this->message,
            'options' => $this->options,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
