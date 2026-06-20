<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\AdminBar;

use PrestoWorld\Contracts\Admin\AdminBar\AdminBarContext as AdminBarContextContract;
use PrestoWorld\Contracts\Admin\AdminBar\AdminBarItem as AdminBarItemContract;

class AdminBarContext implements AdminBarContextContract
{
    protected array $items = [];

    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(AdminBarItemContract $item): void
    {
        $this->items[] = $item;
    }

    public function removeItem(string $id): void
    {
        $this->items = array_values(
            array_filter($this->items, fn(AdminBarItemContract $item) => $item->getId() !== $id),
        );
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(
                fn(AdminBarItemContract $item) => $item->toArray(),
                $this->items,
            ),
        ];
    }
}
