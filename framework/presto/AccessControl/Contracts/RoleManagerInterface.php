<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Contracts;

/**
 * Quản lý vai trò (Role) — dùng cho RBAC và các mô hình constru trên role.
 */
interface RoleManagerInterface
{
    /**
     * @param array<string> $permissions
     */
    public function createRole(string $slug, string $name, array $permissions = [], string $description = ''): void;

    public function deleteRole(string $slug): void;

    /**
     * @return array<string, mixed>|null
     */
    public function getRole(string $slug): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRoles(): array;

    public function grantPermission(string $roleSlug, string $permission): void;

    public function revokePermission(string $roleSlug, string $permission): void;

    public function assignRole(string $subject, string $roleSlug): void;

    public function unassignRole(string $subject, string $roleSlug): void;

    /** @return array<int, string> */
    public function getSubjectRoles(string $subject): array;

    public function hasRole(string $subject, string $roleSlug): bool;
}
