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

    // ── Data Resources ──
    /**
     * @return array<int, array{slug: string, name: string, description?: string, module: string}>
     */
    public function loadDataResources(): array;

    /**
     * @param array<string, mixed> $resource
     */
    public function saveDataResource(array $resource): void;

    public function deleteDataResource(string $slug): void;

    // ── Data Features ──
    /**
     * @return array<int, array{slug: string, name: string, description?: string, resource: string, module: string}>
     */
    public function loadDataFeatures(string $resourceSlug = ''): array;

    /**
     * @param array<string, mixed> $feature
     */
    public function saveDataFeature(array $feature): void;

    public function deleteDataFeature(string $slug): void;

    // ── Module Permissions ──
    /**
     * @return array<int, array{slug: string, name: string, description?: string, module: string, feature?: string, resource?: string}>
     */
    public function loadModulePermissions(string $module = ''): array;

    /**
     * @param array<string, mixed> $permission
     */
    public function saveModulePermission(array $permission): void;

    public function deleteModulePermission(string $slug): void;

    // ── User Groups ──
    /**
     * @return array<int, array{slug: string, name: string, description?: string, module?: string, is_system: bool}>
     */
    public function loadUserGroups(string $module = ''): array;

    /**
     * @param array<string, mixed> $group
     */
    public function saveUserGroup(array $group): void;

    public function deleteUserGroup(string $slug): void;

    // ── Group → Role ──
    /**
     * @return array<int, array{group: string, role: string}>
     */
    public function loadGroupRoles(string $groupSlug = ''): array;

    /**
     * @param array<string> $roles
     */
    public function saveGroupRoles(string $groupSlug, array $roles): void;

    // ── Subject → Group ──
    /**
     * @return array<int, array{subject: string, group: string}>
     */
    public function loadSubjectGroups(string $subject): array;

    /**
     * @param array<string> $groups
     */
    public function saveSubjectGroups(string $subject, array $groups): void;

    // ── Module → Role ──
    /**
     * @return array<int, array{module: string, role: string}>
     */
    public function loadModuleRoles(string $module = ''): array;

    public function saveModuleRole(string $module, string $roleSlug): void;

    public function deleteModuleRole(string $module, string $roleSlug): void;

    // ── Module → Feature ──
    /**
     * @return array<int, array{module: string, feature: string}>
     */
    public function loadModuleFeatures(string $module = ''): array;

    public function saveModuleFeature(string $module, string $featureSlug): void;

    public function deleteModuleFeature(string $module, string $featureSlug): void;
}
