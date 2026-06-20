<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Menu;

interface MenuItem
{
    public function getId(): ?string;
    public function setId(string $id): self;
    public function getScreenId(): ?string;
    public function setScreenId(string $screenId): self;
    public function getLabel(): string;
    public function getUrl(): string;
    public function getIcon(): ?string;
    public function getImage(): ?string;
    public function getAttributes(): array;
    public function getAttribute(string $key, mixed $default = null): mixed;
    public function setAttribute(string $key, mixed $value): self;
    public function getMeta(): array;
    public function setMeta(array $meta): self;
    public function getChildren(): array;
    public function getPriority(): int;
    public function setPriority(int $priority): self;
    public function hasChildren(): bool;
    public function withChild(MenuItem $child): self;
    public function getCapability(): ?string;
    public function setCapability(?string $capability): self;
    public function isVisible(): bool;
    public function toArray(): array;
}
