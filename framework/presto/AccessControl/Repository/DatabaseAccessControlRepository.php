<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl\Repository;

use Cycle\Database\DatabaseInterface;
use PrestoWorld\AccessControl\Contracts\AccessControlRepositoryInterface;

class DatabaseAccessControlRepository implements AccessControlRepositoryInterface
{
    protected DatabaseInterface $db;

    /** @var array<string, non-empty-string> Table names */
    protected array $tables = [
        'permissions' => 'pw_acl_permissions',
        'roles' => 'pw_acl_roles',
        'role_permissions' => 'pw_acl_role_permissions',
        'subject_roles' => 'pw_acl_subject_roles',
        'data_resources' => 'pw_acl_data_resources',
        'data_features' => 'pw_acl_data_features',
        'module_permissions' => 'pw_acl_module_permissions',
        'user_groups' => 'pw_acl_user_groups',
        'group_roles' => 'pw_acl_group_roles',
        'subject_groups' => 'pw_acl_subject_groups',
        'module_roles' => 'pw_acl_module_roles',
        'module_features' => 'pw_acl_module_features',
    ];

    /**
     * @param array<string, non-empty-string> $tables
     */
    public function __construct(DatabaseInterface $db, array $tables = [])
    {
        $this->db = $db;
        if ($tables !== []) {
            $this->tables = array_merge($this->tables, $tables);
        }
    }

    // ── Existing RBAC methods ──────────────────────────────────────

    /** @return array<int, array{subject: string, role: string}> */
    public function loadSubjectRoles(string $subject): array
    {
        if (!$this->db->hasTable($this->tables['subject_roles'])) {
            return [];
        }

        $rows = $this->db->select('subject, role')
            ->from($this->tables['subject_roles'])
            ->where('subject', $subject)
            ->run()
            ->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['subject' => (string) $row['subject'], 'role' => (string) $row['role']];
        }

        return $result;
    }

    /** @return array<int, array{role: string, permission: string}> */
    public function loadRolePermissions(string $roleSlug): array
    {
        if (!$this->db->hasTable($this->tables['role_permissions'])) {
            return [];
        }

        $rows = $this->db->select('role, permission')
            ->from($this->tables['role_permissions'])
            ->where('role', $roleSlug)
            ->run()
            ->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['role' => (string) $row['role'], 'permission' => (string) $row['permission']];
        }

        return $result;
    }

    /** @return array<int, array{slug: string, name: string, description: string, resource: string}> */
    public function loadPermissions(): array
    {
        if (!$this->db->hasTable($this->tables['permissions'])) {
            return [];
        }

        $rows = $this->db->select('slug, name, description, resource, module')
            ->from($this->tables['permissions'])
            ->run()
            ->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'description' => (string) ($row['description'] ?? ''),
                'resource' => (string) ($row['resource'] ?? '*'),
                'module' => $row['module'] !== null ? (string) $row['module'] : null,
            ];
        }

        return $result;
    }

    /** @return array<int, array{slug: string, name: string, description: string}> */
    public function loadRoles(): array
    {
        if (!$this->db->hasTable($this->tables['roles'])) {
            return [];
        }

        $rows = $this->db->select('slug, name, description, module, is_system')
            ->from($this->tables['roles'])
            ->run()
            ->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'description' => (string) ($row['description'] ?? ''),
                'module' => $row['module'] !== null ? (string) $row['module'] : null,
                'is_system' => (bool) $row['is_system'],
            ];
        }

        return $result;
    }

    /**
     * @param array<string> $roles
     */
    public function saveSubjectRoles(string $subject, array $roles): void
    {
        $table = $this->tables['subject_roles'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $createdAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->db->delete($table, ['subject' => $subject])->run();

        foreach ($roles as $role) {
            $this->db->insert($table)->values([
                'subject' => $subject,
                'role' => $role,
                'created_at' => $createdAt,
            ])->run();
        }
    }

    /**
     * @param array<string> $permissions
     */
    public function saveRolePermissions(string $roleSlug, array $permissions): void
    {
        $table = $this->tables['role_permissions'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $createdAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->db->delete($table, ['role' => $roleSlug])->run();

        foreach ($permissions as $permission) {
            $this->db->insert($table)->values([
                'role' => $roleSlug,
                'permission' => $permission,
                'created_at' => $createdAt,
            ])->run();
        }
    }

    /**
     * @param array<string, mixed> $permission
     */
    public function savePermission(array $permission): void
    {
        $table = $this->tables['permissions'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $slug = $permission['slug'];
        $existing = $this->db->select('slug')
            ->from($table)
            ->where('slug', $slug)
            ->run()
            ->fetch();

        if (is_array($existing) && $existing !== []) {
            $this->db->update($table, [
                'name' => $permission['name'],
                'description' => $permission['description'] ?? '',
                'resource' => $permission['resource'] ?? '*',
                'module' => $permission['module'] ?? null,
            ], ['slug' => $slug])->run();
        } else {
            $this->db->insert($table)->values([
                'slug' => $slug,
                'name' => $permission['name'],
                'description' => $permission['description'] ?? '',
                'resource' => $permission['resource'] ?? '*',
                'module' => $permission['module'] ?? null,
            ])->run();
        }
    }

    /**
     * @param array<string, mixed> $role
     */
    public function saveRole(array $role): void
    {
        $table = $this->tables['roles'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $slug = $role['slug'];
        $existing = $this->db->select('slug')
            ->from($table)
            ->where('slug', $slug)
            ->run()
            ->fetch();

        if (is_array($existing) && $existing !== []) {
            $this->db->update($table, [
                'name' => $role['name'],
                'description' => $role['description'] ?? '',
                'module' => $role['module'] ?? null,
                'is_system' => $role['is_system'] ?? false,
            ], ['slug' => $slug])->run();
        } else {
            $this->db->insert($table)->values([
                'slug' => $slug,
                'name' => $role['name'],
                'description' => $role['description'] ?? '',
                'module' => $role['module'] ?? null,
                'is_system' => $role['is_system'] ?? false,
            ])->run();
        }
    }

    public function deletePermission(string $slug): void
    {
        $table = $this->tables['permissions'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->delete($table, ['slug' => $slug])->run();
    }

    public function deleteRole(string $slug): void
    {
        $table = $this->tables['roles'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->delete($table, ['slug' => $slug])->run();
    }

    // ── Data Resources ─────────────────────────────────────────────

    /**
     * @return array<int, array{slug: string, name: string, description?: string, module: string}>
     */
    public function loadDataResources(): array
    {
        if (!$this->db->hasTable($this->tables['data_resources'])) {
            return [];
        }

        $rows = $this->db->select('slug, name, description, module')
            ->from($this->tables['data_resources'])
            ->run()
            ->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'description' => $row['description'] !== null ? (string) $row['description'] : '',
                'module' => (string) $row['module'],
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $resource
     */
    public function saveDataResource(array $resource): void
    {
        $table = $this->tables['data_resources'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $slug = $resource['slug'];
        $existing = $this->db->select('slug')
            ->from($table)
            ->where('slug', $slug)
            ->run()
            ->fetch();

        if (is_array($existing) && $existing !== []) {
            $this->db->update($table, [
                'name' => $resource['name'],
                'description' => $resource['description'] ?? '',
                'module' => $resource['module'],
            ], ['slug' => $slug])->run();
        } else {
            $this->db->insert($table)->values([
                'slug' => $slug,
                'name' => $resource['name'],
                'description' => $resource['description'] ?? '',
                'module' => $resource['module'],
            ])->run();
        }
    }

    public function deleteDataResource(string $slug): void
    {
        $table = $this->tables['data_resources'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->delete($table, ['slug' => $slug])->run();
    }

    // ── Data Features ──────────────────────────────────────────────

    /**
     * @return array<int, array{slug: string, name: string, description?: string, resource: string, module: string}>
     */
    public function loadDataFeatures(string $resourceSlug = ''): array
    {
        if (!$this->db->hasTable($this->tables['data_features'])) {
            return [];
        }

        $query = $this->db->select('slug, name, description, resource, module')
            ->from($this->tables['data_features']);

        if ($resourceSlug !== '') {
            $query = $query->where('resource', $resourceSlug);
        }

        $rows = $query->run()->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'description' => $row['description'] !== null ? (string) $row['description'] : '',
                'resource' => (string) $row['resource'],
                'module' => (string) $row['module'],
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $feature
     */
    public function saveDataFeature(array $feature): void
    {
        $table = $this->tables['data_features'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $slug = $feature['slug'];
        $existing = $this->db->select('slug')
            ->from($table)
            ->where('slug', $slug)
            ->run()
            ->fetch();

        if (is_array($existing) && $existing !== []) {
            $this->db->update($table, [
                'name' => $feature['name'],
                'description' => $feature['description'] ?? '',
                'resource' => $feature['resource'],
                'module' => $feature['module'],
            ], ['slug' => $slug])->run();
        } else {
            $this->db->insert($table)->values([
                'slug' => $slug,
                'name' => $feature['name'],
                'description' => $feature['description'] ?? '',
                'resource' => $feature['resource'],
                'module' => $feature['module'],
            ])->run();
        }
    }

    public function deleteDataFeature(string $slug): void
    {
        $table = $this->tables['data_features'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->delete($table, ['slug' => $slug])->run();
    }

    // ── Module Permissions ─────────────────────────────────────────

    /**
     * @return array<int, array{slug: string, name: string, description?: string, module: string, feature?: string, resource?: string}>
     */
    public function loadModulePermissions(string $module = ''): array
    {
        if (!$this->db->hasTable($this->tables['module_permissions'])) {
            return [];
        }

        $query = $this->db->select('slug, name, description, module, feature, resource')
            ->from($this->tables['module_permissions']);

        if ($module !== '') {
            $query = $query->where('module', $module);
        }

        $rows = $query->run()->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'description' => $row['description'] !== null ? (string) $row['description'] : '',
                'module' => (string) $row['module'],
                'feature' => $row['feature'] !== null ? (string) $row['feature'] : null,
                'resource' => $row['resource'] !== null ? (string) $row['resource'] : null,
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $permission
     */
    public function saveModulePermission(array $permission): void
    {
        $table = $this->tables['module_permissions'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $slug = $permission['slug'];
        $existing = $this->db->select('slug')
            ->from($table)
            ->where('slug', $slug)
            ->run()
            ->fetch();

        if (is_array($existing) && $existing !== []) {
            $this->db->update($table, [
                'name' => $permission['name'],
                'description' => $permission['description'] ?? '',
                'module' => $permission['module'],
                'feature' => $permission['feature'] ?? null,
                'resource' => $permission['resource'] ?? null,
            ], ['slug' => $slug])->run();
        } else {
            $this->db->insert($table)->values([
                'slug' => $slug,
                'name' => $permission['name'],
                'description' => $permission['description'] ?? '',
                'module' => $permission['module'],
                'feature' => $permission['feature'] ?? null,
                'resource' => $permission['resource'] ?? null,
            ])->run();
        }
    }

    public function deleteModulePermission(string $slug): void
    {
        $table = $this->tables['module_permissions'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->delete($table, ['slug' => $slug])->run();
    }

    // ── User Groups ────────────────────────────────────────────────

    /**
     * @return array<int, array{slug: string, name: string, description?: string, module?: string, is_system: bool}>
     */
    public function loadUserGroups(string $module = ''): array
    {
        if (!$this->db->hasTable($this->tables['user_groups'])) {
            return [];
        }

        $query = $this->db->select('slug, name, description, module, is_system')
            ->from($this->tables['user_groups']);

        if ($module !== '') {
            $query = $query->where('module', $module);
        }

        $rows = $query->run()->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'description' => $row['description'] !== null ? (string) $row['description'] : '',
                'module' => $row['module'] !== null ? (string) $row['module'] : null,
                'is_system' => (bool) $row['is_system'],
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $group
     */
    public function saveUserGroup(array $group): void
    {
        $table = $this->tables['user_groups'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $slug = $group['slug'];
        $existing = $this->db->select('slug')
            ->from($table)
            ->where('slug', $slug)
            ->run()
            ->fetch();

        if (is_array($existing) && $existing !== []) {
            $this->db->update($table, [
                'name' => $group['name'],
                'description' => $group['description'] ?? '',
                'module' => $group['module'] ?? null,
                'is_system' => $group['is_system'] ?? false,
            ], ['slug' => $slug])->run();
        } else {
            $this->db->insert($table)->values([
                'slug' => $slug,
                'name' => $group['name'],
                'description' => $group['description'] ?? '',
                'module' => $group['module'] ?? null,
                'is_system' => $group['is_system'] ?? false,
            ])->run();
        }
    }

    public function deleteUserGroup(string $slug): void
    {
        $table = $this->tables['user_groups'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->delete($table, ['slug' => $slug])->run();
    }

    // ── Group → Role ───────────────────────────────────────────────

    /**
     * @return array<int, array{group: string, role: string}>
     */
    public function loadGroupRoles(string $groupSlug = ''): array
    {
        if (!$this->db->hasTable($this->tables['group_roles'])) {
            return [];
        }

        $query = $this->db->select('group, role')
            ->from($this->tables['group_roles']);

        if ($groupSlug !== '') {
            $query = $query->where('group', $groupSlug);
        }

        $rows = $query->run()->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['group' => (string) $row['group'], 'role' => (string) $row['role']];
        }

        return $result;
    }

    /**
     * @param array<string> $roles
     */
    public function saveGroupRoles(string $groupSlug, array $roles): void
    {
        $table = $this->tables['group_roles'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->delete($table, ['group' => $groupSlug])->run();

        foreach ($roles as $role) {
            $this->db->insert($table)->values([
                'group' => $groupSlug,
                'role' => $role,
            ])->run();
        }
    }

    // ── Subject → Group ────────────────────────────────────────────

    /**
     * @return array<int, array{subject: string, group: string}>
     */
    public function loadSubjectGroups(string $subject): array
    {
        if (!$this->db->hasTable($this->tables['subject_groups'])) {
            return [];
        }

        $rows = $this->db->select('subject, group')
            ->from($this->tables['subject_groups'])
            ->where('subject', $subject)
            ->run()
            ->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['subject' => (string) $row['subject'], 'group' => (string) $row['group']];
        }

        return $result;
    }

    /**
     * @param array<string> $groups
     */
    public function saveSubjectGroups(string $subject, array $groups): void
    {
        $table = $this->tables['subject_groups'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->delete($table, ['subject' => $subject])->run();

        foreach ($groups as $group) {
            $this->db->insert($table)->values([
                'subject' => $subject,
                'group' => $group,
            ])->run();
        }
    }

    // ── Module → Role ──────────────────────────────────────────────

    /**
     * @return array<int, array{module: string, role: string}>
     */
    public function loadModuleRoles(string $module = ''): array
    {
        if (!$this->db->hasTable($this->tables['module_roles'])) {
            return [];
        }

        $query = $this->db->select('module, role')
            ->from($this->tables['module_roles']);

        if ($module !== '') {
            $query = $query->where('module', $module);
        }

        $rows = $query->run()->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['module' => (string) $row['module'], 'role' => (string) $row['role']];
        }

        return $result;
    }

    public function saveModuleRole(string $module, string $roleSlug): void
    {
        $table = $this->tables['module_roles'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $existing = $this->db->select('module')
            ->from($table)
            ->where('module', $module)
            ->where('role', $roleSlug)
            ->run()
            ->fetch();

        if (is_array($existing) && $existing === []) {
            $this->db->insert($table)->values([
                'module' => $module,
                'role' => $roleSlug,
            ])->run();
        }
    }

    public function deleteModuleRole(string $module, string $roleSlug): void
    {
        $table = $this->tables['module_roles'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->delete($table, ['module' => $module, 'role' => $roleSlug])->run();
    }

    // ── Module → Feature ───────────────────────────────────────────

    /**
     * @return array<int, array{module: string, feature: string}>
     */
    public function loadModuleFeatures(string $module = ''): array
    {
        if (!$this->db->hasTable($this->tables['module_features'])) {
            return [];
        }

        $query = $this->db->select('module, feature')
            ->from($this->tables['module_features']);

        if ($module !== '') {
            $query = $query->where('module', $module);
        }

        $rows = $query->run()->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['module' => (string) $row['module'], 'feature' => (string) $row['feature']];
        }

        return $result;
    }

    public function saveModuleFeature(string $module, string $featureSlug): void
    {
        $table = $this->tables['module_features'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $existing = $this->db->select('module')
            ->from($table)
            ->where('module', $module)
            ->where('feature', $featureSlug)
            ->run()
            ->fetch();

        if (is_array($existing) && $existing === []) {
            $this->db->insert($table)->values([
                'module' => $module,
                'feature' => $featureSlug,
            ])->run();
        }
    }

    public function deleteModuleFeature(string $module, string $featureSlug): void
    {
        $table = $this->tables['module_features'];
        if (!$this->db->hasTable($table)) {
            return;
        }

        $this->db->delete($table, ['module' => $module, 'feature' => $featureSlug])->run();
    }
}
