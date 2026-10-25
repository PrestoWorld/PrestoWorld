<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Console;

use PrestoWorld\Core\SyncCore\Engine\SyncEngine;
use Witals\Framework\Console\Command;

final class SyncRunJobCommand extends Command
{
    protected string $name = 'sync:run-job';
    protected string $description = 'Run one queued synchronization job (used by the dashboard background runner)';
    protected array $options = [];

    public function handle(array $args): int
    {
        $jobId = (int) ($args[0] ?? $args['job'] ?? 0);
        if ($jobId <= 0) {
            $this->error('Missing job id.');

            return 1;
        }

        $engine = $this->app->make(SyncEngine::class);
        if (!$engine instanceof SyncEngine) {
            $this->error('SyncEngine is not bound.');

            return 1;
        }

        return $engine->run($jobId) ? 0 : 1;
    }
}
