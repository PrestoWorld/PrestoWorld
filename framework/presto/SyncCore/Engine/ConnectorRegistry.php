<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Engine;

use PrestoWorld\Core\SyncCore\Domain\Contracts\ConnectorRegistryInterface;
use PrestoWorld\Core\SyncCore\Domain\Contracts\PlatformConnectorInterface;

final class ConnectorRegistry implements ConnectorRegistryInterface
{
    /** @var array<string, PlatformConnectorInterface> */
    private array $connectors = [];

    public function register(PlatformConnectorInterface $connector): void
    {
        $this->connectors[$connector->key()] = $connector;
    }

    public function get(string $platform): ?PlatformConnectorInterface
    {
        return $this->connectors[$platform] ?? null;
    }

    /**
     * @return array<string, PlatformConnectorInterface>
     */
    public function all(): array
    {
        return $this->connectors;
    }
}
