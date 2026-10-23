<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Contracts;

/**
 * Repository abstraction cho dữ liệu phân quyền, cho phép plugin/module
 * ghi đè bằng storage riêng (SQL, NoSQL, CASL, OPA, …).
 */
interface AccessControlRepositoryInterface
{
    /** @return array<int, array{subject: string, role: string}> */
    public function loadSubjectRoles(string $subject): array;

    /** @return array<int, array{role: string, permission: string}> */
    public function loadRolePermissions(string $roleSlug): array;

    /** @return array<int, array{slug: string, name: string, description: string, resource: string}> */
    public function loadPermissions(): array;

    /** @return array<int, array{slug: string, name: string, description: string}> */
    public function loadRoles(): array;

    /**
     * @param array<string> $roles
     */
    public function saveSubjectRoles(string $subject, array $roles): void;

    /**
     * @param array<string> $permissions
     */
    public function saveRolePermissions(string $roleSlug, array $permissions): void;

    /**
     * @param array<string, mixed> $permission
     */
    public function savePermission(array $permission): void;

    /**
     * @param array<string, mixed> $role
     */
    public function saveRole(array $role): void;

    public function deletePermission(string $slug): void;

    public function deleteRole(string $slug): void;
}
