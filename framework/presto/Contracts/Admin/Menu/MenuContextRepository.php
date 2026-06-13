<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Menu;

interface MenuContextRepository
{
    public function register(string $parentId, MenuItem $item): void;
    public function registerGroup(string $id, string $label, ?string $icon = null, ?string $image = null, array $attributes = [], int $priority = 10): void;
    public function registerItem(string $parentGroup, string $label, string $url, ?string $icon = null, ?string $image = null, array $attributes = [], int $priority = 10): void;

    public function addProvider(MenuProviderInterface $provider): void;
    public function getProviders(): array;

    public function addFilter(callable $filter): void;
    public function getFilters(): array;

    public function getTree(): array;
    public function getTreeAsArray(): array;
    public function toJson(): string;
    public function has(string $id): bool;
    public function remove(string $id): void;
}
