<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Domain\Dto;

/**
 * Describes one synchronizable resource exposed by a platform connector.
 *
 * The definition is intentionally declarative so the generic UI can render
 * settings (enabled directions, API filter options) without knowing anything
 * about the platform itself.
 */
final class ResourceDefinition
{
    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $sort
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $direction,
        public readonly array $filters = [],
        public readonly array $sort = [],
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            direction: (string) ($data['direction'] ?? 'inbound'),
            filters: is_array($data['filters'] ?? null) ? $data['filters'] : [],
            sort: is_array($data['sort'] ?? null) ? $data['sort'] : [],
            extra: is_array($data['extra'] ?? null) ? $data['extra'] : [],
        );
    }

    public function supportsInbound(): bool
    {
        return in_array($this->direction, ['both', 'inbound'], true);
    }

    public function supportsOutbound(): bool
    {
        return in_array($this->direction, ['both', 'outbound'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'direction' => $this->direction,
            'filters' => $this->filters,
            'sort' => $this->sort,
            'extra' => $this->extra,
        ];
    }
}
