<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Jobs;

use PrestoWorld\Core\SyncCore\Engine\SyncEngine;
use Witals\Framework\Queue\Job;

/**
 * Queue transport for one sync job. Only the sync_jobs id is serialized so
 * the worker can resolve repositories and connectors from the container in
 * its own process.
 */
final class RunSyncJob extends Job
{
    public ?string $queue = 'sync';

    public ?int $maxTries = 1;

    public ?int $timeout = 0;

    public function __construct(
        private int $syncJobId,
    ) {
    }

    public function handle(): void
    {
        $engine = app(SyncEngine::class);
        if (!$engine instanceof SyncEngine) {
            throw new \RuntimeException('SyncEngine is not bound in the container.');
        }

        $engine->run($this->syncJobId);
    }

    public function displayName(): string
    {
        return 'sync:job:' . $this->syncJobId;
    }
}
