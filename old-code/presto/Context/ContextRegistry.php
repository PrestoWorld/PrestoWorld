<?php

declare(strict_types=1);

namespace PrestoWorld\Context;

use PrestoWorld\Context\Contracts\ContextInterface;
use PrestoWorld\Context\Contracts\ContextItemInterface;
use PrestoWorld\Context\Contracts\ContextRegistryInterface;
use InvalidArgumentException;

class ContextRegistry implements ContextRegistryInterface
{
    /** @var array<string, ContextInterface> */
    protected array $contexts = [];

    public function define(ContextInterface $context): static
    {
        $this->contexts[$context->getName()] = $context;
        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->contexts[$name]);
    }

    public function get(string $name): ContextInterface
    {
        if (!$this->has($name)) {
            // Lazy-create a generic context if not explicitly defined
            // This allows modules to register items to random slots without pre-definition
            $this->contexts[$name] = new class($name) extends AbstractContext {
                public function __construct(protected string $name) {}
                public function getName(): string { return $this->name; }
                public function getLabel(): string { return ucwords(str_replace(['.', '_'], ' ', $this->name)); }
            };
        }

        return $this->contexts[$name];
    }

    public function add(string $contextName, ContextItemInterface $item): static
    {
        $this->get($contextName)->register($item);
        return $this;
    }

    public function remove(string $contextName, string $itemId): static
    {
        if ($this->has($contextName)) {
            $this->get($contextName)->remove($itemId);
        }
        return $this;
    }

    public function all(): array
    {
        return $this->contexts;
    }

    public function flush(string $contextName): static
    {
        if ($this->has($contextName)) {
            $this->get($contextName)->flush();
        }
        return $this;
    }
}
