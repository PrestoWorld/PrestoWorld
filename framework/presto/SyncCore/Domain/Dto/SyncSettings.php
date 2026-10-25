<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Domain\Dto;

final class SyncSettings
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $platform,
        public readonly bool $inboundEnabled,
        public readonly bool $outboundEnabled,
        public readonly array $data = [],
    ) {
    }

    public function credential(string $key, string $default = ''): string
    {
        $value = $this->data['credentials'][$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->data['options'][$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function resource(string $key): array
    {
        $value = $this->data['resources'][$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'platform' => $this->platform,
            'inbound_enabled' => $this->inboundEnabled,
            'outbound_enabled' => $this->outboundEnabled,
            'settings' => $this->data,
        ];
    }
}
