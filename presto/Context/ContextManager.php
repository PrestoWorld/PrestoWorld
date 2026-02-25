<?php

declare(strict_types=1);

namespace PrestoWorld\Context;

use PrestoWorld\Context\Contracts\ContextRegistryInterface;
use PrestoWorld\Context\Contracts\ContextInterface;
use PrestoWorld\Context\Contracts\ContextItemInterface;

/**
 * Context Manager
 *
 * The primary entry point for managing UI contexts.
 * Usually accessed via app('contexts') or Presto\Context facade.
 */
class ContextManager
{
    public function __construct(
        protected ContextRegistryInterface $registry
    ) {}

    /**
     * Get a named context instance.
     */
    public function context(string $name): ContextInterface
    {
        return $this->registry->get($name);
    }

    /**
     * Register an item into a context.
     * Short syntax for $manager->context($name)->register($item).
     */
    public function register(string $contextName, ContextItemInterface $item): static
    {
        $this->registry->add($contextName, $item);
        return $this;
    }

    /**
     * Resolve context items as plain array for template.
     */
    public function resolve(string $contextName): array
    {
        return $this->registry->get($contextName)->toArray();
    }

    /**
     * Resolve and render items (for contexts like 'home.sections')
     */
    public function render(string $contextName, array $data = []): string
    {
        $items = $this->registry->get($contextName)->resolve();
        $output = '';

        foreach ($items as $item) {
            if (method_exists($item, 'render')) {
                $output .= $item->render($data);
            }
        }

        return $output;
    }

    public function getRegistry(): ContextRegistryInterface
    {
        return $this->registry;
    }
}
