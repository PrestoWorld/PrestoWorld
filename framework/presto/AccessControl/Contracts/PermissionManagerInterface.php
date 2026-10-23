<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Contracts;

/**
 * Quản lý quyền (Permission).
 */
interface PermissionManagerInterface
{
    public function createPermission(string $slug, string $name, string $description = '', string $resource = '*'): void;

    public function deletePermission(string $slug): void;

    /**
     * @return array<string, mixed>|null
     */
    public function getPermission(string $slug): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPermissions(): array;
}
