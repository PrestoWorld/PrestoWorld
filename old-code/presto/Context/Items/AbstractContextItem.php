<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Items;

use PrestoWorld\Context\Contracts\ContextItemInterface;

/**
 * Abstract base for all ContextItem implementations.
 *
 * Provides common fields (id, priority, visible) and their interface methods,
 * so concrete items only need to implement `resolve()` with their own payload.
 */
abstract class AbstractContextItem implements ContextItemInterface
{
    public function __construct(
        protected string $id,
        protected int    $priority = 10,
        protected bool   $visible  = true,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function withPriority(int $priority): static
    {
        $clone = clone $this;
        $clone->priority = $priority;
        return $clone;
    }

    public function withVisibility(bool $visible): static
    {
        $clone = clone $this;
        $clone->visible = $visible;
        return $clone;
    }

    /**
     * Common base fields — subclasses call parent::baseResolve() and merge their own data.
     *
     * @return array<string, mixed>
     */
    protected function baseResolve(): array
    {
        return [
            'id'       => $this->id,
            'priority' => $this->priority,
            'visible'  => $this->visible,
        ];
    }
}
