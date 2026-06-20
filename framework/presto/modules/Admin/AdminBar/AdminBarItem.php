<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\AdminBar;

use PrestoWorld\Contracts\Admin\AdminBar\AdminBarItem as AdminBarItemContract;

class AdminBarItem implements AdminBarItemContract
{
    protected array $children = [];

    public function __construct(
        protected string $id,
        protected string $label,
        protected ?string $icon = null,
        protected ?string $href = null,
        protected string $type = 'link',
        protected string|int|null $badge = null,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBadge(): string|int|null
    {
        return $this->badge;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(AdminBarItemContract $child): void
    {
        $this->children[] = $child;
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
        ];

        if ($this->icon !== null) $data['icon'] = $this->icon;
        if ($this->href !== null) $data['href'] = $this->href;
        if ($this->badge !== null) $data['badge'] = $this->badge;
        if ($this->children !== []) {
            $data['children'] = array_map(
                fn(AdminBarItemContract $child) => $child->toArray(),
                $this->children,
            );
        }

        return $data;
    }
}
