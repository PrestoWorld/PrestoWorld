<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Engine;

use PrestoWorld\Core\SyncCore\Domain\Contracts\ConnectorRegistryInterface;
use PrestoWorld\Core\SyncCore\Domain\Dto\SyncJob;
use PrestoWorld\Core\SyncCore\Domain\Enums\SyncDirection;
use PrestoWorld\Core\SyncCore\Domain\Enums\SyncStatus;
use PrestoWorld\Core\SyncCore\Domain\Enums\SyncTrigger;
use PrestoWorld\Core\SyncCore\Jobs\RunSyncJob;
use PrestoWorld\Core\SyncCore\Storage\SyncJobRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncMappingRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncRecordRepository;
use PrestoWorld\Core\SyncCore\Storage\SyncSettingsRepository;
use Witals\Framework\Application;
use Witals\Framework\Queue\QueueManager;

final class SyncEngine
{
    public function __construct(
        private Application $app,
        private ConnectorRegistryInterface $connectors,
        private SyncSettingsRepository $settings,
        private SyncJobRepository $jobs,
        private SyncRecordRepository $records,
        private SyncMappingRepository $mappings,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function enqueue(
        string $platform,
        SyncDirection $direction,
        string $resource,
        SyncTrigger $trigger = SyncTrigger::MANUAL,
        array $options = [],
    ): SyncJob {
        $connector = $this->connectors->get($platform);
        if ($connector === null) {
            throw new \InvalidArgumentException("Unknown sync platform: {$platform}");
        }

        $definition = $connector->resources()[$resource] ?? null;
        if ($definition === null) {
            throw new \InvalidArgumentException("Unknown sync resource: {$resource}");
        }
        if ($direction === SyncDirection::INBOUND && !$definition->supportsInbound()) {
            throw new \InvalidArgumentException("Resource {$resource} does not support inbound sync.");
        }
        if ($direction === SyncDirection::OUTBOUND && !$definition->supportsOutbound()) {
            throw new \InvalidArgumentException("Resource {$resource} does not support outbound sync.");
        }

        $settings = $this->settings->get($platform, [
            'settings' => $connector->defaultSettings(),
        ]);
        if ($direction === SyncDirection::INBOUND && !$settings->inboundEnabled) {
            throw new \InvalidArgumentException('Inbound sync is disabled for this platform.');
        }
        if ($direction === SyncDirection::OUTBOUND && !$settings->outboundEnabled) {
            throw new \InvalidArgumentException('Outbound sync is disabled for this platform.');
        }

        $id = $this->jobs->create($platform, $direction, $resource, $trigger, $options);
        $job = $this->jobs->find($id);
        if ($job === null) {
            throw new \RuntimeException('Could not create sync job.');
        }

        return $job;
    }

    /**
     * Hand the job to the queue worker. Falls back to a detached CLI process
     * when the queue driver is unavailable (e.g. no worker/DB table yet).
     */
    public function dispatch(int $jobId): void
    {
        if ($this->shouldQueue()) {
            try {
                $manager = $this->app->make(QueueManager::class);
                if ($manager instanceof QueueManager) {
                    $manager->push((new RunSyncJob($jobId))->onQueue('sync'), 'sync');

                    return;
                }
            } catch (\Throwable $e) {
                $this->logDispatchError($jobId, $e->getMessage());
            }
        }

        $this->dispatchProcess($jobId);
    }

    private function shouldQueue(): bool
    {
        if (!$this->app->config('queue.sync_dispatch', true)) {
            return false;
        }

        $connection = (string) $this->app->config('queue.default', 'database');

        return !in_array($connection, ['sync', 'null', ''], true)
            && $this->app->has(QueueManager::class);
    }

    private function dispatchProcess(int $jobId): void
    {
        $base = $this->app->basePath();
        $binary = PHP_BINARY;
        $entry = $base . '/witals';
        $logDir = $this->app->storagePath('logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $log = $logDir . '/sync-jobs.log';

        $command = escapeshellarg($binary)
            . ' ' . escapeshellarg($entry)
            . ' sync:run-job ' . escapeshellarg((string) $jobId)
            . ' >> ' . escapeshellarg($log) . ' 2>&1 &';

        @exec($command);
    }

    private function logDispatchError(int $jobId, string $message): void
    {
        $logDir = $this->app->storagePath('logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        @file_put_contents(
            $logDir . '/sync-jobs.log',
            sprintf("[%s] queue dispatch failed for job %d: %s%s", date('c'), $jobId, $message, PHP_EOL),
            FILE_APPEND,
        );
    }

    public function run(int $jobId): bool
    {
        $job = $this->jobs->find($jobId);
        if ($job === null) {
            return false;
        }
        if (SyncStatus::from($job->status) !== SyncStatus::QUEUED) {
            return false;
        }

        $connector = $this->connectors->get($job->platform);
        if ($connector === null) {
            $this->jobs->finish($jobId, SyncStatus::FAILED, 'Connector not registered: ' . $job->platform);

            return false;
        }

        $direction = SyncDirection::from($job->direction);
        $settings = $this->settings->get($job->platform, [
            'settings' => $connector->defaultSettings(),
        ]);

        $observer = new JobObserver(
            $this->jobs,
            $this->records,
            $this->mappings,
            $connector,
            $jobId,
            $job->platform,
            $job->resource,
            $job->direction,
            $settings->credential('business_id'),
        );

        $this->jobs->markRunning($jobId);

        try {
            $count = $direction === SyncDirection::INBOUND
                ? $connector->runInbound($job->resource, $job->options, $settings, $observer)
                : $connector->runOutbound($job->resource, $job->options, $settings, $observer);

            $observer->flush(true);
            $totals = $observer->totals();
            $this->jobs->progress(
                $jobId,
                $totals['total'],
                $totals['processed'],
                $totals['succeeded'],
                $totals['failed'],
                $totals['skipped'],
                $totals['page'],
            );

            if ($observer->cancelled()) {
                $this->jobs->finish($jobId, SyncStatus::CANCELLED, 'Đã huỷ bởi người dùng');
            } elseif ($totals['failed'] > 0 && $totals['succeeded'] === 0) {
                $this->jobs->finish(
                    $jobId,
                    SyncStatus::FAILED,
                    sprintf('Đồng bộ thất bại: %d lỗi, 0 bản ghi thành công.', $totals['failed']),
                );
            } else {
                $this->jobs->finish(
                    $jobId,
                    SyncStatus::COMPLETED,
                    sprintf(
                        'Đồng bộ xong: %d thành công, %d lỗi, %d bỏ qua.',
                        $totals['succeeded'],
                        $totals['failed'],
                        $totals['skipped'],
                    ),
                );
            }

            return true;
        } catch (\Throwable $e) {
            $observer->flush(true);
            $this->jobs->finish($jobId, SyncStatus::FAILED, $e->getMessage());

            return false;
        }
    }
}
