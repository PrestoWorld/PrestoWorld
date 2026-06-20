<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\Menu;

use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository as MenuContextRepositoryContract;
use PrestoWorld\Contracts\Admin\Menu\MenuItem as MenuItemContract;
use PrestoWorld\Contracts\Admin\Menu\MenuProviderInterface;

class MenuContextRepository implements MenuContextRepositoryContract
{
    protected array $items = [];
    protected array $providers = [];
    protected array $filters = [];
    protected bool $providersLoaded = false;

    public function register(string $parentId, MenuItemContract $item): void
    {
        if ($parentId === '' || $parentId === '#') {
            $this->items[$this->resolveKey($item)] = $item;
            return;
        }

        $parent = $this->findItem($this->items, $parentId);
        if ($parent !== null) {
            $parent->addChild($item);
        }
    }

    public function registerGroup(string $id, string $label, ?string $icon = null, ?string $image = null, array $attributes = [], int $priority = 10): void
    {
        $item = new MenuItem(
            label: $label,
            url: $attributes['url'] ?? '',
            icon: $icon,
            image: $image,
            attributes: $attributes,
            priority: $priority,
        );
        $item->setId($id);
        $this->items[$id] = $item;
    }

    public function registerItem(string $parentGroup, string $label, string $url, ?string $icon = null, ?string $image = null, array $attributes = [], int $priority = 10, ?string $id = null, ?string $screenId = null): void
    {
        $item = new MenuItem(
            label: $label,
            url: $url,
            icon: $icon,
            image: $image,
            attributes: $attributes,
            priority: $priority,
        );

        if ($id !== null) {
            $item->setId($id);
        }

        if ($screenId !== null) {
            $item->setScreenId($screenId);
        }

        if (isset($this->items[$parentGroup])) {
            $this->items[$parentGroup]->addChild($item);
        }
    }

    public function addProvider(MenuProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        $this->providersLoaded = false;
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    public function addFilter(callable $filter): void
    {
        $this->filters[] = $filter;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getTree(): array
    {
        $this->loadProviders();

        $tree = $this->sortItems($this->items);

        foreach ($this->filters as $filter) {
            $tree = $filter($tree);
        }

        return $tree;
    }

    public function getTreeAsArray(): array
    {
        return array_map(
            fn(MenuItemContract $item) => $item->toArray(),
            $this->getTree(),
        );
    }

    public function toJson(): string
    {
        return json_encode($this->getTreeAsArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function has(string $id): bool
    {
        return isset($this->items[$id]);
    }

    public function remove(string $id): void
    {
        unset($this->items[$id]);
    }

    protected function loadProviders(): void
    {
        if ($this->providersLoaded) {
            return;
        }

        $sorted = $this->sortProviders($this->providers);

        foreach ($sorted as $provider) {
            $items = $provider->getMenuItems();
            foreach ($items as $item) {
                $this->items[$this->resolveKey($item)] = $item;
            }
        }

        $this->providersLoaded = true;
    }

    protected function resolveKey(MenuItemContract $item): string
    {
        return 'item_' . spl_object_id($item);
    }

    protected function findItem(array $items, string $id): ?MenuItem
    {
        if (isset($items[$id])) {
            $item = $items[$id];
            if ($item instanceof MenuItem) {
                return $item;
            }
        }

        foreach ($items as $item) {
            if ($item instanceof MenuItem && $item->hasChildren()) {
                $found = $this->findItem($item->getChildren(), $id);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    protected function sortItems(array $items): array
    {
        usort($items, fn(MenuItemContract $a, MenuItemContract $b) => $a->getPriority() <=> $b->getPriority());
        return $items;
    }

    protected function sortProviders(array $providers): array
    {
        usort($providers, fn(MenuProviderInterface $a, MenuProviderInterface $b) => $a->getPriority() <=> $b->getPriority());
        return $providers;
    }
}
