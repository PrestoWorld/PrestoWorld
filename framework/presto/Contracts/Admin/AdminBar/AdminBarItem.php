<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\AdminBar;

interface AdminBarItem
{
    public function getId(): string;
    public function getLabel(): string;
    public function getIcon(): ?string;
    public function getHref(): ?string;
    public function getType(): string;
    public function getBadge(): string|int|null;
    public function getChildren(): array;
    public function addChild(self $child): void;
    public function toArray(): array;
}
