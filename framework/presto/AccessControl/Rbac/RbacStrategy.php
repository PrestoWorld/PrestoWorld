<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Rbac;

use PrestoWorld\AccessControl\Contracts\Strategy\AccessControlStrategyInterface;
use PrestoWorld\AccessControl\Contracts\AccessControlRepositoryInterface;
use PrestoWorld\AccessControl\Exception\PermissionNotFoundException;
use PrestoWorld\AccessControl\Exception\RoleNotFoundException;
use PrestoWorld\AccessControl\Exception\SubjectNotFoundException;

/**
 * Role-Based Access Control (RBAC) với mở rộng phân quyền theo module,
 * data resource, data feature và user group.
 *
 * Quyền được gán cho Role, Role được gán cho User (subject) hoặc User Group.
 * User Group có thể chứa nhiều Role, subject thuộc group sẽ thừa hưởng
 * quyền của group.
 *
 * Module-level permissions: module đăng ký data resource, data feature,
 * module permission riêng. Kiểm tra `can()` sẽ xét cả role permission
 * lẫn module permission.
 *
 * Có thể mở rộng thành RBAC hierarchical hoặc constrained RBAC bằng
 * plugin/module khác mà không sửa code này.
 */
class RbacStrategy implements AccessControlStrategyInterface
{
    public const MODEL = 'rbac';
    public const VERSION = '1.1.0';

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

    /** @var array<string, array<string, mixed>> slug => data resource meta */
    protected array $dataResources = [];

    /** @var array<string, array<string, mixed>> slug => data feature meta */
    protected array $dataFeatures = [];

    /** @var array<string, array<string, mixed>> slug => module permission meta */
    protected array $modulePermissions = [];

    /** @var array<string, array<string, mixed>> slug => user group meta */
    protected array $userGroups = [];

    /** @var array<string, array<int, string>> groupSlug => [role slugs] */
    protected array $groupRoles = [];

    /** @var array<string, array<int, string>> subjectId => [group slugs] */
    protected array $subjectGroups = [];

    /** @var array<string, array<int, string>> module => [role slugs] */
    protected array $moduleRoles = [];

    /** @var array<string, array<int, string>> module => [feature slugs] */
    protected array $moduleFeatures = [];

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
            $this->rolePermissions[$row['role']][] = $row['permission'];
        }

        foreach ($this->repository->loadSubjectRoles('__all__') as $row) {
            $this->subjectRoles[$row['subject']][] = $row['role'];
        }

        // ── Data Resources ──
        foreach ($this->repository->loadDataResources() as $res) {
            $this->dataResources[$res['slug']] = $res;
        }

        // ── Data Features ──
        foreach ($this->repository->loadDataFeatures() as $feat) {
            $this->dataFeatures[$feat['slug']] = $feat;
        }

        // ── Module Permissions ──
        foreach ($this->repository->loadModulePermissions() as $perm) {
            $this->modulePermissions[$perm['slug']] = $perm;
        }

        // ── User Groups ──
        foreach ($this->repository->loadUserGroups() as $group) {
            $this->userGroups[$group['slug']] = $group;
        }

        foreach ($this->repository->loadGroupRoles('__all__') as $row) {
            $this->groupRoles[$row['group']][] = $row['role'];
        }

        foreach ($this->repository->loadSubjectRoles('__all__') as $row) {
            // subject_groups được load riêng; loadSubjectRoles('__all__') ở trên
            // đã lấy subject_roles. Ở đây chúng ta load subject_groups:
        }

        // Load subject → group mappings
        $allSubjects = $this->collectAllSubjects();
        foreach ($allSubjects as $subject) {
            foreach ($this->repository->loadSubjectGroups($subject) as $row) {
                $this->subjectGroups[$row['subject']][] = $row['group'];
            }
        }

        // ── Module → Role ──
        foreach ($this->repository->loadModuleRoles() as $row) {
            $this->moduleRoles[$row['module']][] = $row['role'];
        }

        // ── Module → Feature ──
        foreach ($this->repository->loadModuleFeatures() as $row) {
            $this->moduleFeatures[$row['module']][] = $row['feature'];
        }

        $this->initialized = true;
    }

    /**
     * Thu thập danh sách tất cả subject đã có trong hệ thống (dùng để load
     * subject_groups lần đầu). Nếu repository không hỗ trợ, trả về [].
     *
     * @return array<int, string>
     */
    protected function collectAllSubjects(): array
    {
        $subjects = [];
        $seen = [];

        // Từ subject_roles
        foreach ($this->subjectRoles as $subject => $roles) {
            if (!isset($seen[$subject])) {
                $seen[$subject] = true;
                $subjects[] = $subject;
            }
        }

        // Từ subject_groups đã load (nếu có)
        foreach ($this->subjectGroups as $subject => $groups) {
            if (!isset($seen[$subject])) {
                $seen[$subject] = true;
                $subjects[] = $subject;
            }
        }

        return $subjects;
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

        // 1. Kiểm tra qua Role (subject trực tiếp và qua group)
        $roles = $this->getEffectiveRoles($subject);

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

        // 2. Kiểm tra qua Module Permission (action có dạng module:feature hoặc resource:feature)
        $modulePermResult = $this->checkModulePermission($subject, $action, $resource, $context);
        if ($modulePermResult['allowed']) {
            return $modulePermResult;
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
        $roles = $this->getEffectiveRoles($subject);

        foreach ($roles as $roleSlug) {
            foreach (($this->rolePermissions[$roleSlug] ?? []) as $perm) {
                $result[$perm] = true;
            }
        }

        // Thêm module permissions có效 lực với subject
        $modulePerms = $this->getEffectiveModulePermissions($subject);
        foreach ($modulePerms as $perm) {
            $result[$perm] = true;
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
            'module' => null,
            'is_system' => false,
        ];

        if ($this->repository !== null) {
            $this->repository->saveRole($this->roles[$slug]);
        }

        foreach ($permissions as $perm) {
            if (!isset($this->permissions[$perm])) {
                $this->createPermission($perm, $perm);
            }
            $this->grantPermission($slug, $perm);
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

        // Bỏ role khỏi mọi group
        foreach ($this->groupRoles as $group => &$roles) {
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
        return in_array($roleSlug, $this->getEffectiveRoles($subject), true);
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
            'module' => null,
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

    // ── Data Resource / Feature / Module Permission management ─────

    /**
     * Tạo data resource (ví dụ: orders, products, customers).
     *
     * @param array<string, mixed> $resource
     */
    public function createDataResource(array $resource): void
    {
        $slug = $resource['slug'];
        if (isset($this->dataResources[$slug])) {
            throw new \RuntimeException("Data resource '{$slug}' already exists.");
        }

        $this->dataResources[$slug] = $resource;

        if ($this->repository !== null) {
            $this->repository->saveDataResource($resource);
        }
    }

    public function getDataResource(string $slug): ?array
    {
        return $this->dataResources[$slug] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDataResources(): array
    {
        return array_values($this->dataResources);
    }

    public function deleteDataResource(string $slug): void
    {
        unset($this->dataResources[$slug]);

        // Xóa features thuộc resource này
        foreach ($this->dataFeatures as $featSlug => $feat) {
            if ($feat['resource'] === $slug) {
                $this->deleteDataFeature($featSlug);
            }
        }

        if ($this->repository !== null) {
            $this->repository->deleteDataResource($slug);
        }
    }

    /**
     * Tạo data feature (ví dụ: orders.view, orders.edit, products.export).
     *
     * @param array<string, mixed> $feature
     */
    public function createDataFeature(array $feature): void
    {
        $slug = $feature['slug'];
        if (isset($this->dataFeatures[$slug])) {
            throw new \RuntimeException("Data feature '{$slug}' already exists.");
        }

        $this->dataFeatures[$slug] = $feature;

        if ($this->repository !== null) {
            $this->repository->saveDataFeature($feature);
        }
    }

    public function getDataFeature(string $slug): ?array
    {
        return $this->dataFeatures[$slug] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDataFeatures(string $resourceSlug = ''): array
    {
        $result = [];
        foreach ($this->dataFeatures as $feat) {
            if ($resourceSlug === '' || $feat['resource'] === $resourceSlug) {
                $result[] = $feat;
            }
        }

        return $result;
    }

    public function deleteDataFeature(string $slug): void
    {
        unset($this->dataFeatures[$slug]);

        if ($this->repository !== null) {
            $this->repository->deleteDataFeature($slug);
        }
    }

    /**
     * Tạo module permission (gắn với module + feature + resource).
     *
     * @param array<string, mixed> $permission
     */
    public function createModulePermission(array $permission): void
    {
        $slug = $permission['slug'];
        if (isset($this->modulePermissions[$slug])) {
            throw new \RuntimeException("Module permission '{$slug}' already exists.");
        }

        $this->modulePermissions[$slug] = $permission;

        if ($this->repository !== null) {
            $this->repository->saveModulePermission($permission);
        }
    }

    public function getModulePermission(string $slug): ?array
    {
        return $this->modulePermissions[$slug] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listModulePermissions(string $module = ''): array
    {
        $result = [];
        foreach ($this->modulePermissions as $perm) {
            if ($module === '' || $perm['module'] === $module) {
                $result[] = $perm;
            }
        }

        return $result;
    }

    public function deleteModulePermission(string $slug): void
    {
        unset($this->modulePermissions[$slug]);

        if ($this->repository !== null) {
            $this->repository->deleteModulePermission($slug);
        }
    }

    // ── User Group management ──────────────────────────────────────

    /**
     * Tạo user group (nhóm người dùng).
     *
     * @param array<string, mixed> $group
     */
    public function createUserGroup(array $group): void
    {
        $slug = $group['slug'];
        if (isset($this->userGroups[$slug])) {
            throw new \RuntimeException("User group '{$slug}' already exists.");
        }

        $this->userGroups[$slug] = $group;

        if ($this->repository !== null) {
            $this->repository->saveUserGroup($group);
        }
    }

    public function getUserGroup(string $slug): ?array
    {
        return $this->userGroups[$slug] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listUserGroups(string $module = ''): array
    {
        $result = [];
        foreach ($this->userGroups as $group) {
            if ($module === '' || ($group['module'] ?? null) === $module) {
                $result[] = $group;
            }
        }

        return $result;
    }

    public function deleteUserGroup(string $slug): void
    {
        unset($this->userGroups[$slug]);
        unset($this->groupRoles[$slug]);

        // Bỏ gán group này ở mọi subject
        foreach ($this->subjectGroups as $subject => &$groups) {
            $groups = array_values(array_filter($groups, fn(string $g) => $g !== $slug));
        }
        unset($groups);

        if ($this->repository !== null) {
            $this->repository->deleteUserGroup($slug);
        }
    }

    /**
     * Gán role cho group (group thành viên sẽ thừa hưởng role này).
     */
    public function grantGroupRole(string $groupSlug, string $roleSlug): void
    {
        if (!isset($this->userGroups[$groupSlug])) {
            throw new \RuntimeException("User group '{$groupSlug}' not found.");
        }
        if (!isset($this->roles[$roleSlug])) {
            throw RoleNotFoundException::for($roleSlug);
        }

        $roles = &$this->groupRoles[$groupSlug];
        if ($roles === null) {
            $roles = [];
        }
        if (!in_array($roleSlug, $roles, true)) {
            $roles[] = $roleSlug;
        }
        unset($roles);

        if ($this->repository !== null) {
            $this->repository->saveGroupRoles($groupSlug, $this->groupRoles[$groupSlug]);
        }
    }

    public function revokeGroupRole(string $groupSlug, string $roleSlug): void
    {
        $this->groupRoles[$groupSlug] = array_values(
            array_filter($this->groupRoles[$groupSlug] ?? [], fn(string $r) => $r !== $roleSlug)
        );

        if ($this->repository !== null) {
            $this->repository->saveGroupRoles($groupSlug, $this->groupRoles[$groupSlug]);
        }
    }

    /**
     * @return array<int, string>
     */
    public function getGroupRoles(string $groupSlug): array
    {
        return $this->groupRoles[$groupSlug] ?? [];
    }

    /**
     * Gán subject vào group.
     */
    public function assignSubjectToGroup(string $subject, string $groupSlug): void
    {
        if (!isset($this->userGroups[$groupSlug])) {
            throw new \RuntimeException("User group '{$groupSlug}' not found.");
        }

        $groups = &$this->subjectGroups[$subject];
        if ($groups === null) {
            $groups = [];
        }
        if (!in_array($groupSlug, $groups, true)) {
            $groups[] = $groupSlug;
        }
        unset($groups);

        if ($this->repository !== null) {
            $this->repository->saveSubjectGroups($subject, $this->subjectGroups[$subject]);
        }
    }

    public function unassignSubjectFromGroup(string $subject, string $groupSlug): void
    {
        $this->subjectGroups[$subject] = array_values(
            array_filter($this->subjectGroups[$subject] ?? [], fn(string $g) => $g !== $groupSlug)
        );

        if ($this->repository !== null) {
            $this->repository->saveSubjectGroups($subject, $this->subjectGroups[$subject]);
        }
    }

    /**
     * @return array<int, string>
     */
    public function getSubjectGroups(string $subject): array
    {
        return $this->subjectGroups[$subject] ?? [];
    }

    public function hasSubjectInGroup(string $subject, string $groupSlug): bool
    {
        return in_array($groupSlug, $this->getSubjectGroups($subject), true);
    }

    // ── Module → Role / Feature registration ───────────────────────

    /**
     * Đăng ký role thuộc về module (module có quyền quản lý role này).
     * Không yêu cầu role đã tồn tại trong strategy này (role có thể được
     * quản lý bởi AccessControlManager instance khác).
     */
    public function registerModuleRole(string $module, string $roleSlug): void
    {
        $roles = &$this->moduleRoles[$module];
        if ($roles === null) {
            $roles = [];
        }
        if (!in_array($roleSlug, $roles, true)) {
            $roles[] = $roleSlug;
        }
        unset($roles);

        if ($this->repository !== null) {
            $this->repository->saveModuleRole($module, $roleSlug);
        }
    }

    public function unregisterModuleRole(string $module, string $roleSlug): void
    {
        $this->moduleRoles[$module] = array_values(
            array_filter($this->moduleRoles[$module] ?? [], fn(string $r) => $r !== $roleSlug)
        );

        if ($this->repository !== null) {
            $this->repository->deleteModuleRole($module, $roleSlug);
        }
    }

    /**
     * @return array<int, string>
     */
    public function getModuleRoles(string $module): array
    {
        return $this->moduleRoles[$module] ?? [];
    }

    /**
     * Đăng ký feature thuộc module.
     */
    public function registerModuleFeature(string $module, string $featureSlug): void
    {
        if (!isset($this->dataFeatures[$featureSlug])) {
            throw new \RuntimeException("Data feature '{$featureSlug}' not found.");
        }

        $features = &$this->moduleFeatures[$module];
        if ($features === null) {
            $features = [];
        }
        if (!in_array($featureSlug, $features, true)) {
            $features[] = $featureSlug;
        }
        unset($features);

        if ($this->repository !== null) {
            $this->repository->saveModuleFeature($module, $featureSlug);
        }
    }

    public function unregisterModuleFeature(string $module, string $featureSlug): void
    {
        $this->moduleFeatures[$module] = array_values(
            array_filter($this->moduleFeatures[$module] ?? [], fn(string $f) => $f !== $featureSlug)
        );

        if ($this->repository !== null) {
            $this->repository->deleteModuleFeature($module, $featureSlug);
        }
    }

    /**
     * @return array<int, string>
     */
    public function getModuleFeatures(string $module): array
    {
        return $this->moduleFeatures[$module] ?? [];
    }

    // ── Internal ──────────────────────────────────────────────────

    /**
     * Lấy tất cả role hiệu lực của subject (role trực tiếp + role qua group).
     *
     * @return array<int, string>
     */
    protected function getEffectiveRoles(string $subject): array
    {
        $roles = [];

        // Role trực tiếp của subject
        foreach ($this->getSubjectRoles($subject) as $roleSlug) {
            $roles[$roleSlug] = $roleSlug;
        }

        // Role qua group
        foreach ($this->getSubjectGroups($subject) as $groupSlug) {
            foreach ($this->getGroupRoles($groupSlug) as $roleSlug) {
                $roles[$roleSlug] = $roleSlug;
            }
        }

        return array_values($roles);
    }

    /**
     * Kiểm tra module permission dựa trên action string.
     * Action có thể có dạng: `module:feature` (ví dụ: `orders:view`) hoặc
     * `resource:feature` (ví dụ: `orders.view`).
     *
     * @param array<string, mixed> $context
     */
    protected function checkModulePermission(string $subject, string $action, mixed $resource, array $context): array
    {
        // Phân tích action: module:feature hoặc resource.feature
        $parts = str_contains($action, ':') ? explode(':', $action, 2) : explode('.', $action, 2);
        if (count($parts) !== 2) {
            return ['allowed' => false, 'reason' => 'Action không đúng định dạng module:feature', 'model' => self::MODEL];
        }

        [$moduleOrResource, $feature] = $parts;

        // Tìm module permission khớp
        foreach ($this->modulePermissions as $perm) {
            if ($perm['slug'] !== $action && $perm['name'] !== $action) {
                continue;
            }

            $module = $perm['module'] ?? '';
            $permFeature = $perm['feature'] ?? null;

            // Kiểm tra feature khớp
            if ($permFeature !== null && $permFeature !== $feature) {
                continue;
            }

            // Kiểm tra module có trong danh sách module của subject không
            $subjectModules = $this->getSubjectModules($subject);
            if (!in_array($module, $subjectModules, true)) {
                continue;
            }

            // Kiểm tra resource nếu có
            if ($resource !== null && isset($perm['resource']) && $perm['resource'] !== null) {
                if ((string) $resource !== $perm['resource']) {
                    continue;
                }
            }

            return ['allowed' => true, 'reason' => "Được phép qua module permission '{$perm['slug']}'", 'model' => self::MODEL];
        }

        return ['allowed' => false, 'reason' => "Không tìm thấy module permission cho '{$action}'", 'model' => self::MODEL];
    }

    /**
     * Lấy danh sách module permissions hiệu lực của subject (qua role của
     * group và role trực tiếp).
     *
     * @return array<int, string>
     */
    protected function getEffectiveModulePermissions(string $subject): array
    {
        $perms = [];

        // Lấy module từ role của subject (qua group roles có module)
        foreach ($this->getEffectiveRoles($subject) as $roleSlug) {
            $role = $this->roles[$roleSlug] ?? null;
            if ($role && isset($role['module']) && $role['module'] !== null) {
                $perms[$role['module']] = $role['module'];
            }
        }

        // Lấy module từ module_roles đăng ký
        foreach ($this->moduleRoles as $module => $roles) {
            foreach ($roles as $roleSlug) {
                if (in_array($roleSlug, $this->getEffectiveRoles($subject), true)) {
                    $perms[$module] = $module;
                }
            }
        }

        // Lấy module permissions từ các module trên
        $result = [];
        foreach ($perms as $module) {
            foreach ($this->modulePermissions as $perm) {
                if ($perm['module'] === $module) {
                    $result[] = $perm['slug'];
                }
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Lấy danh sách module mà subject có quyền truy cập (qua role/group).
     *
     * @return array<int, string>
     */
    protected function getSubjectModules(string $subject): array
    {
        $modules = [];

        foreach ($this->getEffectiveRoles($subject) as $roleSlug) {
            $role = $this->roles[$roleSlug] ?? null;
            if ($role && isset($role['module']) && $role['module'] !== null) {
                $modules[$role['module']] = $role['module'];
            }
        }

        foreach ($this->moduleRoles as $module => $roles) {
            foreach ($roles as $roleSlug) {
                if (in_array($roleSlug, $this->getEffectiveRoles($subject), true)) {
                    $modules[$module] = $module;
                }
            }
        }

        return array_values($modules);
    }

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
