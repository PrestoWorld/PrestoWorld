<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Rbac;

use PrestoWorld\AccessControl\Contracts\Strategy\AccessControlStrategyInterface;
use PrestoWorld\AccessControl\Contracts\AccessControlRepositoryInterface;
use PrestoWorld\AccessControl\Exception\PermissionNotFoundException;
use PrestoWorld\AccessControl\Exception\RoleNotFoundException;
use PrestoWorld\AccessControl\Exception\SubjectNotFoundException;

/**
 * Role-Based Access Control (RBAC).
 *
 * Quyền được gán cho Role, Role được gán cho User (subject).
 * Kiểm tra: user có role nào, role đó có quyền action trên resource không.
 *
 * Có thể mở rộng thành RBAC hierarchical (role kế thừa role) hoặc
 * constrained RBAC (phân biệt static/dynamic separation of duty) bằng
 * plugin/module khác mà không sửa code này.
 */
class RbacStrategy implements AccessControlStrategyInterface
{
    public const MODEL = 'rbac';
    public const VERSION = '1.0.0';

    /** @var AccessControlRepositoryInterface|null */
    protected ?AccessControlRepositoryInterface $repository = null;

    /** @var array<string, array<int, string>> roleSlug => [permissions] */
    protected array $rolePermissions = [];

    /** @var array<string, array<int, string>> subjectId => [role slugs] */
    protected array $subjectRoles = [];

    /** @var array<string, array<string, mixed>> slug => permission meta */
    protected array $permissions = [];

    /** @var array<string, array<string, mixed>> slug => role meta */
    protected array $roles = [];

    protected bool $initialized = false;

    public function setRepository(AccessControlRepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }

    public function repository(): ?AccessControlRepositoryInterface
    {
        return $this->repository;
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        if ($this->repository === null) {
            $this->initialized = true;
            return;
        }

        // Load permissions & roles từ repository
        foreach ($this->repository->loadPermissions() as $perm) {
            $this->permissions[$perm['slug']] = $perm;
        }

        foreach ($this->repository->loadRoles() as $role) {
            $this->roles[$role['slug']] = $role;
        }

        foreach ($this->repository->loadRolePermissions('__all__') as $row) {
            // repository trả toàn bộ mapping role->permission
            $this->rolePermissions[$row['role']][] = $row['permission'];
        }

        foreach ($this->repository->loadSubjectRoles('__all__') as $row) {
            $this->subjectRoles[$row['subject']][] = $row['role'];
        }

        $this->initialized = true;
    }

    public function isReady(): bool
    {
        return $this->initialized;
    }

    public function modelName(): string
    {
        return self::MODEL;
    }

    public function model(): string
    {
        return self::MODEL;
    }

    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * {@inheritDoc}
     */
    public function can(string $subject, string $action, mixed $resource = null, array $context = []): array
    {
        if (!$this->isReady()) {
            $this->initialize();
        }

        $roles = $this->getSubjectRoles($subject);

        foreach ($roles as $roleSlug) {
            $perms = $this->rolePermissions[$roleSlug] ?? [];

            // Wildcard permission cho phép mọi action
            if (in_array('*', $perms, true) || in_array($action, $perms, true)) {
                // Kiểm tra scope resource nếu có
                if ($resource !== null && !$this->resourceAllowed($roleSlug, $action, $resource, $context)) {
                    return ['allowed' => false, 'reason' => "Role '{$roleSlug}' không được phép trên resource này", 'model' => self::MODEL];
                }

                return ['allowed' => true, 'reason' => "Được phép qua role '{$roleSlug}'", 'model' => self::MODEL];
            }
        }

        return ['allowed' => false, 'reason' => "Subject '{$subject}' không có quyền '{$action}'", 'model' => self::MODEL];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, bool>
     */
    public function actions(string $subject, mixed $resource = null, array $context = []): array
    {
        if (!$this->isReady()) {
            $this->initialize();
        }

        $result = [];
        $roles = $this->getSubjectRoles($subject);

        foreach ($roles as $roleSlug) {
            foreach (($this->rolePermissions[$roleSlug] ?? []) as $perm) {
                $result[$perm] = true;
            }
        }

        // Đảm bảo luôn có ít nhất một entry
        if ($result === []) {
            $result['none'] = false;
        }

        return $result;
    }

    // ── RoleManagerInterface ──────────────────────────────────────

    /**
     * @param array<string> $permissions
     */
    public function createRole(string $slug, string $name, array $permissions = [], string $description = ''): void
    {
        if (isset($this->roles[$slug])) {
            throw RoleNotFoundException::for($slug);
        }

        $this->roles[$slug] = [
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
        ];

        foreach ($permissions as $perm) {
            $this->grantPermission($slug, $perm);
        }

        if ($this->repository !== null) {
            $this->repository->saveRole($this->roles[$slug]);
            $this->repository->saveRolePermissions($slug, $permissions);
        }
    }

    public function deleteRole(string $slug): void
    {
        if (!isset($this->roles[$slug])) {
            throw RoleNotFoundException::for($slug);
        }

        unset($this->roles[$slug]);
        unset($this->rolePermissions[$slug]);

        // Bỏ gán role này ở mọi subject
        foreach ($this->subjectRoles as $subject => &$roles) {
            $roles = array_values(array_filter($roles, fn(string $r) => $r !== $slug));
        }
        unset($roles);

        if ($this->repository !== null) {
            $this->repository->deleteRole($slug);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRole(string $slug): ?array
    {
        return $this->roles[$slug] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRoles(): array
    {
        return array_values($this->roles);
    }

    public function grantPermission(string $roleSlug, string $permission): void
    {
        if (!isset($this->roles[$roleSlug])) {
            throw RoleNotFoundException::for($roleSlug);
        }

        $perms = &$this->rolePermissions[$roleSlug];
        if ($perms === null) {
            $perms = [];
        }
        if (!in_array($permission, $perms, true)) {
            $perms[] = $permission;
        }
        unset($perms);

        if ($this->repository !== null) {
            $this->repository->saveRolePermissions($roleSlug, $this->rolePermissions[$roleSlug]);
        }
    }

    public function revokePermission(string $roleSlug, string $permission): void
    {
        if (!isset($this->roles[$roleSlug])) {
            throw RoleNotFoundException::for($roleSlug);
        }

        $this->rolePermissions[$roleSlug] = array_values(
            array_filter($this->rolePermissions[$roleSlug] ?? [], fn(string $p) => $p !== $permission)
        );

        if ($this->repository !== null) {
            $this->repository->saveRolePermissions($roleSlug, $this->rolePermissions[$roleSlug]);
        }
    }

    public function assignRole(string $subject, string $roleSlug): void
    {
        if (!isset($this->roles[$roleSlug])) {
            throw RoleNotFoundException::for($roleSlug);
        }

        $roles = &$this->subjectRoles[$subject];
        if ($roles === null) {
            $roles = [];
        }
        if (!in_array($roleSlug, $roles, true)) {
            $roles[] = $roleSlug;
        }
        unset($roles);

        if ($this->repository !== null) {
            $this->repository->saveSubjectRoles($subject, $this->subjectRoles[$subject]);
        }
    }

    public function unassignRole(string $subject, string $roleSlug): void
    {
        $this->subjectRoles[$subject] = array_values(
            array_filter($this->subjectRoles[$subject] ?? [], fn(string $r) => $r !== $roleSlug)
        );

        if ($this->repository !== null) {
            $this->repository->saveSubjectRoles($subject, $this->subjectRoles[$subject]);
        }
    }

    /**
     * @return array<int, string>
     */
    public function getSubjectRoles(string $subject): array
    {
        return $this->subjectRoles[$subject] ?? [];
    }

    public function hasRole(string $subject, string $roleSlug): bool
    {
        return in_array($roleSlug, $this->getSubjectRoles($subject), true);
    }

    // ── PermissionManagerInterface ────────────────────────────────

    public function createPermission(string $slug, string $name, string $description = '', string $resource = '*'): void
    {
        if (isset($this->permissions[$slug])) {
            throw PermissionNotFoundException::for($slug);
        }

        $this->permissions[$slug] = [
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'resource' => $resource,
        ];

        if ($this->repository !== null) {
            $this->repository->savePermission($this->permissions[$slug]);
        }
    }

    public function deletePermission(string $slug): void
    {
        if (!isset($this->permissions[$slug])) {
            throw PermissionNotFoundException::for($slug);
        }

        unset($this->permissions[$slug]);

        // Gỡ quyền này khỏi mọi role
        foreach ($this->rolePermissions as $roleSlug => &$perms) {
            $perms = array_values(array_filter($perms, fn(string $p) => $p !== $slug));
        }
        unset($perms);

        if ($this->repository !== null) {
            $this->repository->deletePermission($slug);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPermission(string $slug): ?array
    {
        return $this->permissions[$slug] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPermissions(): array
    {
        return array_values($this->permissions);
    }

    // ── Internal ──────────────────────────────────────────────────

    /**
     * Kiểm tra resource-level scope (có thể mở rộng bởi plugin ABAC/PBAC).
     * Mặc định RBAC không phân biệt resource instance, nên trả về true.
     *
     * @param array<string, mixed> $context
     */
    protected function resourceAllowed(string $roleSlug, string $action, mixed $resource, array $context): bool
    {
        return true;
    }
}
