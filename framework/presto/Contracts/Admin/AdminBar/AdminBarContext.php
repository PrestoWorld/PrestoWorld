<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\AdminBar;

interface AdminBarContext
{
    public function getItems(): array;
    public function addItem(AdminBarItem $item): void;
    public function removeItem(string $id): void;
    public function toArray(): array;
}
