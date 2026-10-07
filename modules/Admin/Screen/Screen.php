<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\Screen;

use PrestoWorld\Contracts\Admin\Screen\ScreenInterface;

class Screen implements ScreenInterface
{
    public function __construct(
        protected string $id,
        protected string $title,
        protected ?string $parent = null,
        protected ?string $capability = null,
        protected ?string $icon = null,
        protected int $position = 10,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function getCapability(): ?string
    {
        return $this->capability;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'parent' => $this->parent,
            'capability' => $this->capability,
            'icon' => $this->icon,
            'position' => $this->position,
        ];
    }
}
