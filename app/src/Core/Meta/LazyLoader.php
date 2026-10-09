<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Meta;

use PrestoWorld\Core\MetaRepository;

/**
 * LazyLoader — WP_Metadata_Lazyloader replacement (spec 10 §10.5.6).
 */
final class LazyLoader
{
    /** @var list<array{objectId: int, metaKey: string, metaType: string}> */
    private array $pending = [];

    public function __construct()
    {
    }

    public function add(int $objectId, string $metaKey, string $metaType = 'post'): void
    {
        $this->pending[] = [
            'objectId' => $objectId,
            'metaKey' => $metaKey,
            'metaType' => $metaType,
        ];
    }

    public function load(): int
    {
        $count = 0;
        foreach ($this->pending as $item) {
            MetaRepository::get($item['metaType'], $item['objectId'], $item['metaKey'], true);
            $count++;
        }
        $this->pending = [];

        return $count;
    }

    /**
     * @return array<int, array{objectId: int, metaKey: string, metaType: string}>
     */
    public function pending(): array
    {
        return $this->pending;
    }

    public function reset(): void
    {
        $this->pending = [];
    }
}
