<?php

declare(strict_types=1);

namespace PrestoWorld\Context\Items;

/**
 * A single navigation menu item.
 *
 * Can be a top-level link or have children (sub-items / dropdown groups).
 * Modules and plugins register these into Header/Footer/Portal contexts.
 */
class MenuItemContext extends AbstractContextItem
{
    /** @var self[] */
    protected array $children = [];

    /** @var DropdownGroupContext[] */
    protected array $groups = [];

    public function __construct(
        string           $id,
        protected string $label,
        protected string $url         = '#',
        protected string $icon        = '',
        protected string $description = '',
        int              $priority    = 10,
        bool             $visible     = true,
        protected array  $attributes  = [],   // e.g. ['class' => 'active']
        protected ?string $badge      = null, // e.g. 'PRO', 'NEW'
        protected string  $target     = '_self',
    ) {
        parent::__construct($id, $priority, $visible);
    }

    public function addChild(self $child): static
    {
        $this->children[$child->getId()] = $child;
        return $this;
    }

    public function addGroup(DropdownGroupContext $group): static
    {
        $this->groups[$group->getId()] = $group;
        return $this;
    }

    /** @return DropdownGroupContext[] */
    public function getGroups(): array
    {
        $sorted = array_values($this->groups);
        usort($sorted, fn(DropdownGroupContext $a, DropdownGroupContext $b) => $a->getPriority() <=> $b->getPriority());
        return $sorted;
    }

    /** @return MenuItemContext[] */
    public function getChildren(): array
    {
        $sorted = array_values($this->children);
        usort($sorted, fn(MenuItemContext $a, MenuItemContext $b) => $a->getPriority() <=> $b->getPriority());
        return $sorted;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function resolve(): array
    {
        return array_merge($this->baseResolve(), [
            'label'        => $this->label,
            'url'          => $this->url,
            'icon'         => $this->icon,
            'description'  => $this->description,
            'attributes'   => $this->attributes,
            'badge'        => $this->badge,
            'target'       => $this->target,
            'has_children' => $this->hasChildren() || !empty($this->groups),
            'children'     => array_map(fn(self $c) => $c->resolve(), $this->getChildren()),
            'groups'       => array_map(fn(DropdownGroupContext $g) => $g->resolve(), $this->getGroups()),
        ]);
    }
}
