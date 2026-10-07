<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\Menu;

use PrestoWorld\Contracts\Admin\Menu\MenuItem as MenuItemContract;

class MenuItem implements MenuItemContract
{
    protected ?string $id = null;
    protected ?string $screenId = null;
    protected array $children = [];
    protected array $meta = [];
    protected ?string $capability = null;

    public function __construct(
        protected string $label,
        protected string $url = '',
        protected ?string $icon = null,
        protected ?string $image = null,
        protected array $attributes = [],
        protected int $priority = 10,
    ) {}

    public static function fromArray(array $data): self
    {
        $item = new self(
            label: $data['label'] ?? '',
            url: $data['url'] ?? '',
            icon: $data['icon'] ?? null,
            image: $data['image'] ?? null,
            attributes: $data['attributes'] ?? [],
            priority: $data['priority'] ?? 10,
        );

        if (isset($data['id'])) {
            $item->id = $data['id'];
        }

        if (isset($data['screenId'])) {
            $item->screenId = $data['screenId'];
        }

        if (isset($data['meta']) && is_array($data['meta'])) {
            $item->meta = $data['meta'];
        }

        if (isset($data['capability'])) {
            $item->capability = $data['capability'];
        }

        if (isset($data['children']) && is_array($data['children'])) {
            foreach ($data['children'] as $childData) {
                $item->children[] = is_array($childData)
                    ? self::fromArray($childData)
                    : $childData;
            }
        }

        return $item;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getScreenId(): ?string
    {
        return $this->screenId;
    }

    public function setScreenId(string $screenId): self
    {
        $this->screenId = $screenId;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    public function withChild(MenuItemContract $child): self
    {
        $clone = clone $this;
        $clone->children[] = $child;
        return $clone;
    }

    public function addChild(MenuItemContract $child): void
    {
        $this->children[] = $child;
    }

    public function getCapability(): ?string
    {
        return $this->capability;
    }

    public function setCapability(?string $capability): self
    {
        $this->capability = $capability;
        return $this;
    }

    public function isVisible(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'label' => $this->label,
            'url' => $this->url,
            'priority' => $this->priority,
        ];

        if ($this->screenId !== null) {
            $data['screenId'] = $this->screenId;
        }

        if ($this->icon !== null) {
            $data['icon'] = $this->icon;
        }

        if ($this->image !== null) {
            $data['image'] = $this->image;
        }

        if ($this->attributes !== []) {
            $data['attributes'] = $this->attributes;
        }

        if ($this->meta !== []) {
            $data['meta'] = $this->meta;
        }

        if ($this->capability !== null) {
            $data['capability'] = $this->capability;
        }

        if ($this->children !== []) {
            $data['children'] = array_map(
                fn(MenuItemContract $child) => $child->toArray(),
                $this->sortChildren($this->children),
            );
        }

        return $data;
    }

    protected function sortChildren(array $children): array
    {
        usort($children, fn(MenuItemContract $a, MenuItemContract $b) => $a->getPriority() <=> $b->getPriority());
        return $children;
    }
}
