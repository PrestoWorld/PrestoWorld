<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Engine;

use PrestoWorld\Core\SyncCore\Domain\Contracts\PlatformConnectorInterface;
use PrestoWorld\Core\SyncCore\Domain\Contracts\SyncObserverInterface;
use PrestoWorld\Core\SyncCore\Domain\Enums\RecordStatus;
use PrestoWorld\Core\SyncCore\Storage\SyncJobRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncMappingRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncRecordRepository;

/**
 * Persists per-record progress for a single job. Counters are buffered so a
 * 100-record page does not issue 100 UPDATE statements.
 */
final class JobObserver implements SyncObserverInterface
{
    private int $processed = 0;
    private int $succeeded = 0;
    private int $failed = 0;
    private int $skipped = 0;
    private int $total = 0;
    private int $page = 0;
    private int $buffer = 0;
    private int $lastCancelCheck = 0;
    private bool $cancelled = false;

    public function __construct(
        private SyncJobRepository $jobs,
        private SyncRecordRepository $records,
        private SyncMappingRepository $mappings,
        private PlatformConnectorInterface $connector,
        private int $jobId,
        private string $platform,
        private string $resource,
        private string $direction,
        private string $businessId = '',
    ) {
    }

    public function page(int $page, int $items): void
    {
        $this->page = $page;
        $this->total = max($this->total, $this->processed + $items);
        $this->flush(true);
    }

    public function record(
        string $externalId,
        string $localId,
        RecordStatus $status,
        string $action = '',
        ?string $message = null,
        array $payload = [],
    ): void {
        $this->records->add(
            $this->jobId,
            $this->platform,
            $this->resource,
            $this->direction,
            $externalId,
            $localId,
            $status,
            $action,
            $message,
            $payload,
        );

        $this->processed++;
        match ($status) {
            RecordStatus::SUCCESS => $this->succeeded++,
            RecordStatus::FAILED => $this->failed++,
            RecordStatus::SKIPPED => $this->skipped++,
        };

        if ($status === RecordStatus::SUCCESS && $externalId !== '' && $localId !== '') {
            $label = '';
            foreach (['name', 'title', 'code', 'sku'] as $key) {
                if (is_scalar($payload[$key] ?? null) && (string) $payload[$key] !== '') {
                    $label = (string) $payload[$key];
                    break;
                }
            }

            $this->map($externalId, $localId, array_filter([
                'action' => $action,
                'label' => $label,
            ], static fn ($value) => $value !== ''));
        }

        $this->buffer++;
        if ($this->buffer >= 20) {
            $this->flush();
        }
    }

    public function map(string $externalId, string $localId, array $meta = []): void
    {
        if ($externalId === '' || $localId === '') {
            return;
        }

        $this->mappings->upsert(
            $this->platform,
            $this->resource,
            $externalId,
            $localId,
            $this->businessId,
            $meta,
        );
    }

    public function cancelled(): bool
    {
        if (!$this->cancelled && (time() - $this->lastCancelCheck) >= 2) {
            $this->lastCancelCheck = time();
            $this->cancelled = $this->jobs->isCancelled($this->jobId);
        }

        return $this->cancelled;
    }

    public function flush(bool $force = false): void
    {
        if (!$force && $this->buffer === 0) {
            return;
        }

        $this->jobs->progress(
            $this->jobId,
            $this->total,
            $this->processed,
            $this->succeeded,
            $this->failed,
            $this->skipped,
            $this->page,
        );
        $this->buffer = 0;
    }

    public function totals(): array
    {
        return [
            'total' => $this->total,
            'processed' => $this->processed,
            'succeeded' => $this->succeeded,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'page' => $this->page,
        ];
    }
}
