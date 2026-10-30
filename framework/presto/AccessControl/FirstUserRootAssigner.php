<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl;

use Cycle\Database\DatabaseInterface;

final class FirstUserRootAssigner
{
    /** @var array<string, string> */
    private array $tables;

    public function __construct(
        private readonly DatabaseInterface $db,
        string $prefix = '',
    ) {
        $this->tables = [
            'roles' => $prefix . 'acl_roles',
            'permissions' => $prefix . 'acl_permissions',
            'role_permissions' => $prefix . 'acl_role_permissions',
            'subject_roles' => $prefix . 'acl_subject_roles',
        ];
    }

    public function assign(string $subject): void
    {
        $tables = [
            $this->tables['roles'],
            $this->tables['permissions'],
            $this->tables['role_permissions'],
            $this->tables['subject_roles'],
        ];
        foreach ($tables as $table) {
            if (!$this->db->hasTable($table)) {
                throw new \RuntimeException("Cannot assign the root role: table {$table} is missing.");
            }
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $rootRole = $this->db->select('slug')
            ->from($this->tables['roles'])
            ->where('slug', 'root')
            ->run()
            ->fetch();
        if (!$rootRole) {
            $this->db->insert($this->tables['roles'])->values([
                'slug' => 'root',
                'name' => 'Root',
                'description' => 'Toàn quyền quản trị hệ thống và truy cập mọi BookPress site',
                'module' => null,
                'is_system' => true,
                'created_at' => $now,
            ])->run();
        }

        $wildcardPermission = $this->db->select('slug')
            ->from($this->tables['permissions'])
            ->where('slug', '*')
            ->run()
            ->fetch();
        if (!$wildcardPermission) {
            $this->db->insert($this->tables['permissions'])->values([
                'slug' => '*',
                'name' => 'Toàn quyền',
                'description' => 'Toàn quyền hệ thống',
                'resource' => '*',
                'module' => null,
                'created_at' => $now,
            ])->run();
        }

        $rootPermission = $this->db->select('role')
            ->from($this->tables['role_permissions'])
            ->where('role', 'root')
            ->where('permission', '*')
            ->run()
            ->fetch();
        if (!$rootPermission) {
            $this->db->insert($this->tables['role_permissions'])->values([
                'role' => 'root',
                'permission' => '*',
                'created_at' => $now,
            ])->run();
        }

        $rootAssignment = $this->db->select('role')
            ->from($this->tables['subject_roles'])
            ->where('subject', $subject)
            ->where('role', 'root')
            ->run()
            ->fetch();
        if (!$rootAssignment) {
            $this->db->insert($this->tables['subject_roles'])->values([
                'subject' => $subject,
                'role' => 'root',
                'created_at' => $now,
            ])->run();
        }
    }
}
