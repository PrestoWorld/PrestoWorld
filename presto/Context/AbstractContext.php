<?php

declare(strict_types=1);

namespace PrestoWorld\Context;

use PrestoWorld\Context\Contracts\ContextInterface;
use PrestoWorld\Context\Contracts\ContextItemInterface;

/**
 * Abstract base implementation for all Contexts.
 *
 * Subclasses only need to define a name and label.
 * Registration, sorting, and resolution are handled here.
 */
abstract class AbstractContext implements ContextInterface
{
    /** @var array<string, ContextItemInterface> */
    protected array $items = [];

    abstract public function getName(): string;

    abstract public function getLabel(): string;

    public function register(ContextItemInterface $item): static
    {
        $this->items[$item->getId()] = $item;
        return $this;
    }

    public function remove(string $itemId): static
    {
        unset($this->items[$itemId]);
        return $this;
    }

    /**
     * Resolve all visible items sorted by priority (ascending).
     *
     * @return ContextItemInterface[]
     */
    public function resolve(): array
    {
        $visible = array_filter($this->items, fn(ContextItemInterface $item) => $item->isVisible());

        usort($visible, fn(ContextItemInterface $a, ContextItemInterface $b) => $a->getPriority() <=> $b->getPriority());

        return array_values($visible);
    }

    /**
     * Return all resolved items as plain arrays for template rendering.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            fn(ContextItemInterface $item) => $item->resolve(),
            $this->resolve()
        );
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function flush(): static
    {
        $this->items = [];
        return $this;
    }
}
