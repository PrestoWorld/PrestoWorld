<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Menu;

interface MenuProviderInterface
{
    public function getIdentifier(): string;
    public function getMenuItems(): array;
    public function getPriority(): int;
}
