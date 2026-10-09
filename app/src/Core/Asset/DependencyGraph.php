<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Asset;

/**
 * Replaces WP_Dependencies — handle/dependency graph with cycle-safe resolution.
 */
class DependencyGraph
{
    /** @var array<string, array{src: string, deps: list<string>, extra: array<string, mixed>}> */
    private array $items = [];

    public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $item
     */
    public function add(string $handle, array $item = []): void
    {
        $src = '';
        if (isset($item['src']) && is_scalar($item['src'])) {
            $src = (string) $item['src'];
        }

        $deps = [];
        if (isset($item['deps']) && is_array($item['deps'])) {
            foreach ($item['deps'] as $dep) {
                if (is_string($dep)) {
                    $deps[] = $dep;
                }
            }
        }

        $extra = [];
        if (isset($item['extra']) && is_array($item['extra'])) {
            foreach ($item['extra'] as $key => $value) {
                $extra[(string) $key] = $value;
            }
        }

        $this->items[$handle] = ['src' => $src, 'deps' => $deps, 'extra' => $extra];
    }

    public function has(string $handle): bool
    {
        return isset($this->items[$handle]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $handle): ?array
    {
        return $this->items[$handle] ?? null;
    }

    /**
     * @return list<string>
     */
    public function deps(string $handle): array
    {
        return $this->items[$handle]['deps'] ?? [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return list<string>
     */
    public function resolve(string $handle): array
    {
        $order = [];
        $visited = [];
        $stack = [];
        $this->visit($handle, $order, $visited, $stack);

        return $order;
    }

    public function reset(): void
    {
        $this->items = [];
    }

    /**
     * @param list<string> $order
     * @param array<string, bool> $visited
     * @param array<string, bool> $stack
     */
    private function visit(string $handle, array &$order, array &$visited, array &$stack): void
    {
        if (isset($visited[$handle]) || isset($stack[$handle])) {
            return;
        }

        $stack[$handle] = true;

        foreach ($this->deps($handle) as $dep) {
            $this->visit($dep, $order, $visited, $stack);
        }

        unset($stack[$handle]);
        $visited[$handle] = true;
        $order[] = $handle;
    }
}
