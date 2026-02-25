<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Items;

/**
 * A named group of links inside a nav dropdown column.
 *
 * Corresponds to one column in a multi-column nav dropdown.
 * Each group has a title (e.g. "Cloud & Infrastructure") and child MenuItemContexts.
 */
class DropdownGroupContext extends AbstractContextItem
{
    /** @var MenuItemContext[] */
    protected array $items = [];

    public function __construct(
        string           $id,
        protected string $title,
        int              $priority = 10,
        bool             $visible  = true,
    ) {
        parent::__construct($id, $priority, $visible);
    }

    public function add(MenuItemContext $item): static
    {
        $this->items[$item->getId()] = $item;
        return $this;
    }

    /** @return MenuItemContext[] */
    public function getItems(): array
    {
        $sorted = array_values($this->items);
        usort($sorted, fn(MenuItemContext $a, MenuItemContext $b) => $a->getPriority() <=> $b->getPriority());
        return $sorted;
    }

    public function resolve(): array
    {
        return array_merge($this->baseResolve(), [
            'title' => $this->title,
            'items' => array_map(fn(MenuItemContext $i) => $i->resolve(), $this->getItems()),
        ]);
    }
}
