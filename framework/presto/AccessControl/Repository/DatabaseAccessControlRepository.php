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

    /** @return array<int, array{subject: string, role: string}> */
    public function loadSubjectRoles(string $subject): array
    {
        if (!$this->db->hasTable($this->tables['subject_roles'])) {
            return [];
        }

        $rows = $this->db->select('subject, role')
            ->from($this->tables['subject_roles'])
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

        $rows = $this->db->select('slug, name, description, resource')
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

        $rows = $this->db->select('slug, name, description')
            ->from($this->tables['roles'])
            ->run()
            ->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'description' => (string) ($row['description'] ?? ''),
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

        $this->db->delete($table, ['subject' => $subject])->run();

        foreach ($roles as $role) {
            $this->db->insert($table)->values([
                'subject' => $subject,
                'role' => $role,
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

        $this->db->delete($table, ['role' => $roleSlug])->run();

        foreach ($permissions as $permission) {
            $this->db->insert($table)->values([
                'role' => $roleSlug,
                'permission' => $permission,
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
                'description' => $permission['description'],
                'resource' => $permission['resource'],
            ], ['slug' => $slug])->run();
        } else {
            $this->db->insert($table)->values([
                'slug' => $slug,
                'name' => $permission['name'],
                'description' => $permission['description'],
                'resource' => $permission['resource'],
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
                'description' => $role['description'],
            ], ['slug' => $slug])->run();
        } else {
            $this->db->insert($table)->values([
                'slug' => $slug,
                'name' => $role['name'],
                'description' => $role['description'],
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
}
