<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Domain\Contracts;

use PrestoWorld\Core\SyncCore\Domain\Enums\RecordStatus;

interface SyncObserverInterface
{
    /**
     * Called when a page has been fetched (inbound) or a batch was submitted
     * (outbound), before/around the per-record callbacks.
     */
    public function page(int $page, int $items): void;

    /**
     * Called once per synchronized record.
     *
     * @param array<string, mixed> $payload
     */
    public function record(
        string $externalId,
        string $localId,
        RecordStatus $status,
        string $action = '',
        ?string $message = null,
        array $payload = [],
    ): void;

    /**
     * Persist the mapping between the external platform id and the local id.
     *
     * @param array<string, mixed> $meta
     */
    public function map(string $externalId, string $localId, array $meta = []): void;

    /**
     * True when the job was cancelled from the UI; long-running handlers
     * should stop as soon as possible.
     */
    public function cancelled(): bool;
}
