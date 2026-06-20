<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Menu;

interface MenuSection
{
    public function getId(): string;
    public function getTitle(): string;
    public function getPriority(): int;
    public function getItems(): array;
    public function addItem(MenuItem $item): void;
    public function toArray(): array;
}
