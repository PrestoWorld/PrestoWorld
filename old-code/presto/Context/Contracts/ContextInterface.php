<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contracts;

/**
 * Interface for a Context – a named placeholder in a UI section.
 *
 * A Context is a named "slot" in the UI (e.g. "header", "dashboard.widgets").
 * Modules, plugins, and controllers register ContextItems into it.
 * The theme/template then resolves the context to get ordered, visible items.
 */
interface ContextInterface
{
    /**
     * Unique identifier/name for this context (e.g. "header", "footer.menu").
     */
    public function getName(): string;

    /**
     * Human-readable label for this context.
     */
    public function getLabel(): string;

    /**
     * Register a new item into this context.
     */
    public function register(ContextItemInterface $item): static;

    /**
     * Remove a registered item by its ID.
     */
    public function remove(string $itemId): static;

    /**
     * Resolve all visible items, sorted by priority.
     *
     * @return ContextItemInterface[]
     */
    public function resolve(): array;

    /**
     * Return all resolved items as plain data arrays (for template injection).
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array;

    /**
     * Check whether any items are registered.
     */
    public function isEmpty(): bool;

    /**
     * Remove all registered items.
     */
    public function flush(): static;
}
