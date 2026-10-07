<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\Menu;

use PrestoWorld\Contracts\Admin\Menu\MenuItem as MenuItemContract;
use PrestoWorld\Contracts\Admin\Menu\MenuSection as MenuSectionContract;

class MenuSection implements MenuSectionContract
{
    protected array $items = [];

    public function __construct(
        protected string $id,
        protected string $title,
        protected int $priority = 10,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(MenuItemContract $item): void
    {
        $this->items[] = $item;
    }

    public function toArray(): array
    {
        $items = $this->items;
        usort($items, fn(MenuItemContract $a, MenuItemContract $b) => $a->getPriority() <=> $b->getPriority());

        return [
            'id' => $this->id,
            'title' => $this->title,
            'priority' => $this->priority,
            'items' => array_map(fn(MenuItemContract $item) => $item->toArray(), $items),
        ];
    }
}
