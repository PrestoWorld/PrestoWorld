<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Domain\Contracts;

interface ConnectorRegistryInterface
{
    public function register(PlatformConnectorInterface $connector): void;

    public function get(string $platform): ?PlatformConnectorInterface;

    /**
     * @return array<string, PlatformConnectorInterface>
     */
    public function all(): array;
}
