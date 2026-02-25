<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contracts;

/**
 * Interface for a single item that can be registered inside a Context.
 *
 * A ContextItem represents a discrete UI element (menu item, widget, section, etc.)
 * that modules/plugins register into a Context slot.
 */
interface ContextItemInterface
{
    /**
     * Unique identifier for this item within the context.
     */
    public function getId(): string;

    /**
     * Priority order for rendering (lower = rendered first).
     */
    public function getPriority(): int;

    /**
     * Whether this item should currently be rendered.
     */
    public function isVisible(): bool;

    /**
     * Resolve and return the item's data payload for template rendering.
     *
     * @return array<string, mixed>
     */
    public function resolve(): array;
}
