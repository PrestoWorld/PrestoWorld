<?php

declare(strict_types=1);

namespace PrestoWorld\Admin\Menu;

use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository as MenuContextRepositoryContract;
use PrestoWorld\Contracts\Admin\Menu\MenuItem as MenuItemContract;
use PrestoWorld\Contracts\Admin\Menu\MenuProviderInterface;

class MenuContextRepository implements MenuContextRepositoryContract
{
    protected array $sections = [];
    protected array $items = [];
    protected array $providers = [];
    protected array $filters = [];
    protected bool $providersLoaded = false;

    // Core sidebar sections (top-level categories)
    private const CORE_SECTIONS = [
        'overview' => ['label' => 'Overview', 'icon' => 'LayoutDashboard', 'priority' => 0],
        'ecommerce' => ['label' => 'Ecommerce', 'icon' => 'ShoppingBag', 'priority' => 10],
        'analytics' => ['label' => 'Analytics', 'icon' => 'BarChart3', 'priority' => 30],
        'crm' => ['label' => 'CRM', 'icon' => 'MessageSquare', 'priority' => 40],
        'modules' => ['label' => 'Modules / Plugins', 'icon' => 'Puzzle', 'priority' => 50],
        'appearance' => ['label' => 'Appearance', 'icon' => 'Palette', 'priority' => 60],
        'tools' => ['label' => 'Tools', 'icon' => 'Wrench', 'priority' => 70],
        'settings' => ['label' => 'Cài Đặt', 'icon' => 'Settings', 'priority' => 80],
    ];

    public function __construct()
    {
        $this->registerCoreSections();
    }

    private function registerCoreSections(): void
    {
        foreach (self::CORE_SECTIONS as $id => $def) {
            $this->sections[$id] = [
                'id' => $id,
                'label' => $def['label'],
                'icon' => $def['icon'],
                'priority' => $def['priority'],
                'groups' => [],
            ];
        }
    }

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
        // Find which section this group belongs to
        $sectionId = $attributes['section'] ?? 'overview';
        
        if (!isset($this->sections[$sectionId])) {
            // Default to overview if section doesn't exist
            $sectionId = 'overview';
        }

        // Idempotent: multiple modules may register the same group
        if (isset($this->sections[$sectionId]['groups'][$id])) {
            return;
        }

        $item = new MenuItem(
            label: $label,
            url: $attributes['url'] ?? '',
            icon: $icon,
            image: $image,
            attributes: $attributes,
            priority: $priority,
        );
        $item->setId($id);
        $this->sections[$sectionId]['groups'][$id] = $item;
    }

    public function registerItem(string $parentGroup, string $label, string $url, ?string $icon = null, ?string $image = null, array $attributes = [], int $priority = 10, ?string $id = null, ?string $screenId = null): void
    {
        // Find which section contains this group
        $foundSection = null;
        foreach ($this->sections as $sectionId => $section) {
            if (isset($section['groups'][$parentGroup])) {
                $foundSection = $sectionId;
                break;
            }
        }

        if ($foundSection === null) {
            $foundSection = 'overview';
        }

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

        $this->sections[$foundSection]['groups'][$parentGroup]->addChild($item);
    }

    public function registerSection(string $id, string $label, ?string $icon = null, int $priority = 10): void
    {
        if (isset($this->sections[$id])) {
            return; // Already registered
        }

        $this->sections[$id] = [
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'priority' => $priority,
            'groups' => [],
        ];
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

        $tree = [];
        
        // Sort sections by priority
        uasort($this->sections, fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($this->sections as $section) {
            // Only include sections that have groups
            if (empty($section['groups'])) {
                continue;
            }

            // Sort groups by priority
            $groups = $section['groups'];
            uasort($groups, fn(MenuItemContract $a, MenuItemContract $b) => $a->getPriority() <=> $b->getPriority());

            // Apply filters to groups
            foreach ($this->filters as $filter) {
                $groups = $filter($groups);
            }

            $tree[] = [
                'id' => $section['id'],
                'label' => $section['label'],
                'icon' => $section['icon'],
                'priority' => $section['priority'],
                // array_values: groups are keyed by id internally; serialize
                // as a JSON list — consumers iterate it with for...of.
                'groups' => array_values(array_map(fn(MenuItemContract $item) => $item->toArray(), $groups)),
            ];
        }

        return $tree;
    }

    public function getTreeAsArray(): array
    {
        return $this->getTree();
    }

    public function getSections(): array
    {
        return array_values($this->getTree());
    }

    public function toJson(): string
    {
        return json_encode($this->getTreeAsArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function has(string $id): bool
    {
        return isset($this->items[$id]) || $this->findSectionOrGroup($id) !== null;
    }

    public function remove(string $id): void
    {
        unset($this->items[$id]);
        
        // Also check sections and groups
        foreach ($this->sections as $sectionId => $section) {
            if (isset($section['groups'][$id])) {
                unset($this->sections[$sectionId]['groups'][$id]);
                return;
            }
        }
    }

    private function findSectionOrGroup(string $id): ?MenuItem
    {
        foreach ($this->sections as $section) {
            if (isset($section['groups'][$id])) {
                return $section['groups'][$id];
            }
        }
        return null;
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