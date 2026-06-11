<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Contracts;

/**
 * Interface for the Context Registry.
 *
 * The registry is the central store that holds all registered Context instances.
 * Modules/plugins interact with this registry to add items to named contexts.
 */
interface ContextRegistryInterface
{
    /**
     * Register a new context definition.
     * If a context with the same name already exists, it is replaced.
     */
    public function define(ContextInterface $context): static;

    /**
     * Check whether a named context has been defined.
     */
    public function has(string $name): bool;

    /**
     * Retrieve a named context.
     *
     * @throws \InvalidArgumentException if context is not defined.
     */
    public function get(string $name): ContextInterface;

    /**
     * Register an item into a named context.
     * If the context does not exist yet, it will be auto-created lazily.
     */
    public function add(string $contextName, ContextItemInterface $item): static;

    /**
     * Remove an item from a named context.
     */
    public function remove(string $contextName, string $itemId): static;

    /**
     * Return all defined contexts.
     *
     * @return array<string, ContextInterface>
     */
    public function all(): array;

    /**
     * Flush/clear all items from a context (but keep the context definition).
     */
    public function flush(string $contextName): static;
}
