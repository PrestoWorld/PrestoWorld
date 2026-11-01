<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Contracts;

interface AdminRegistryInterface
{
    public function registerPage(AdminPageInterface $page): void;

    public function getPage(string $slug): ?AdminPageInterface;

    public function getPages(): array;

    public function getMenuTree(): array;
}
