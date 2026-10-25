<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Domain\Contracts;

use PrestoWorld\Core\SyncCore\Domain\Dto\ResourceDefinition;
use PrestoWorld\Core\SyncCore\Domain\Dto\SyncSettings;
use PrestoWorld\Core\SyncCore\Domain\Enums\SyncDirection;

/**
 * A platform adapter (Nhanh.vn, KiotViet, Sapo...).
 *
 * The generic sync engine only talks to this contract; adding a new platform
 * means implementing the interface and registering it in the connector
 * registry, without touching jobs/records/mappings or the UI.
 */
interface PlatformConnectorInterface
{
    /** Stable machine key, e.g. "nhanh". */
    public function key(): string;

    /** Human label shown in the dashboard, e.g. "Nhanh.vn". */
    public function label(): string;

    public function icon(): string;

    /** @return array<string, ResourceDefinition> */
    public function resources(): array;

    /**
     * JSON-serializable schema used by the settings screen. Supported field
     * types: text, password, number, toggle, select, textarea.
     *
     * @return list<array<string, mixed>>
     */
    public function settingsSchema(): array;

    /**
     * @return array<string, mixed>
     */
    public function defaultSettings(): array;

    /**
     * @param array<string, mixed> $options
     */
    public function runInbound(
        string $resource,
        array $options,
        SyncSettings $settings,
        SyncObserverInterface $observer,
    ): int;

    /**
     * @param array<string, mixed> $options
     */
    public function runOutbound(
        string $resource,
        array $options,
        SyncSettings $settings,
        SyncObserverInterface $observer,
    ): int;

    /**
     * Resolve the local (Bookowl) record id after an inbound item is stored.
     *
     * @param array<string, mixed> $item
     */
    public function resolveLocalId(string $resource, array $item): ?string;

    /**
     * Validate and normalize settings coming from the UI.
     *
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    public function validateSettings(array $settings): array;

    /**
     * Cheap connectivity probe for the "Test connection" button.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(SyncSettings $settings): array;
}
